<?php

class FeedParser
{
    /**
     * kind of feed
     *
     * @var string
     */
    private $kind = 'rss20';

    /**
     * feed data
     *
     * @var string
     */
    private $feed;

    /**
     * @var array
     */
    private $rss20_channel_elements = [
        'title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'pubDate',
        'lastBuildDate', 'category', 'generator', 'docs', 'cloud', 'ttl', 'image', 'rating', 'textinput',
        'skipHours', 'skipDays',
    ];

    /**
     * @var array
     */
    private $rss20_item_elements = [
        'title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid',
        'pubDate', 'sourse', 'dc:creator', 'dc:date', 'dc:subject',
    ];

    /**
     * @var array
     */
    private $atom10_feed_elements = [
        'author', 'category', 'conributor', 'generator', 'icon', 'id', 'link', 'logo', 'rights',
        'subtitle', 'title', 'updated', 'modified',
    ];

    /**
     * @var array
     */
    private $atom10_entry_elements = [
        'author', 'category', 'content', 'contributor', 'id', 'link', 'published', 'rights',
        'source', 'summary', 'title', 'updated', 'issued', 'modified', 'created'
    ];

    /**
     * @var array
     */
    private $kind_of_datetime = [
        'published', 'updated', 'pubDate', 'dc:date', 'issued'
    ];

    public function __construct($url, $kind)
    {
        $this->setKind($kind);
        $this->openFeed($url);
    }

    public function setKind($kind)
    {
        if (array_search($kind, ['rss20', 'atom10'], true) !== false) {
            $this->kind = $kind;
            return true;
        }

        return false;
    }

    public function get()
    {
        // Feedの取得に失敗した場合
        if (empty($this->feed)) {
            return null;
        }

        if ($this->kind === 'rss20') {
            $meta_term = 'channel';
            $item_term = 'item';
            $meta_elem = $this->rss20_channel_elements;
            $item_elem = $this->rss20_item_elements;
        } elseif ($this->kind === 'atom10') {
            $meta_term = 'feed';
            $item_term = 'entry';
            $meta_elem = $this->atom10_feed_elements;
            $item_elem = $this->atom10_entry_elements;
        } else {
            $meta_term = '';
            $item_term = '';
            $meta_elem = [];
            $item_elem = [];
        }
        // get encoding
        $res['meta']['encoding'] = preg_match('@encoding=[\'\"](.*?)[\'\"]@', $this->feed, $match) ? $match[1] : false;

        // get element [ channel || feed ]
        $channel = preg_match("@<$meta_term.*?>(.*?)<$item_term.*?>@si", $this->feed, $match) ? $match[1] : false;

        foreach ($meta_elem as $element) {
            $res['meta'][$element] = preg_match("@<$element>(.*?)</$element>@si", $channel, $match) ? $match[1] : null;
            if (empty($res['meta'][$element])) {
                $attr = preg_match("@<$element(.*?)>@si", $this->feed, $match) ? $match[1] : null;
                if (!is_null($attr)) {
                    $res['meta'][$element] = $this->get_attr($attr);
                }
            }
        }

        // get element [ item || entry ]
        preg_match_all("@<$item_term.*?>(.*?)</$item_term>@si", $this->feed, $items);
        $i = 1;
        foreach (array_shift($items) as $row) {
            foreach ($item_elem as $element) {
                $res['items'][$i][$element] = preg_match(
                    "@<$element.*?>(.*?)</$element>@si",
                    $row,
                    $match
                ) ? str_replace(['<![CDATA[', ']]>', '\r\n', '	'], '', $match[1]) : null;

                if (empty($res['items'][$i][$element])) {
                    $attr = preg_match("@<$element(.*?)>@si", $row, $match) ? $match[1] : null;
                    if (!is_null($attr)) {
                        $res['items'][$i][$element] = $this->get_attr($attr);
                    }
                }

                // fix some of
                if ($element === 'author' && $this->kind === 'atom10') {
                    $author = preg_match(
                        '@^<name>(.*?)</name>$@',
                        $res['items'][$i]['author'],
                        $match
                    ) ? $match[1] : null;

                    if (!is_null($author)) {
                        $res['items'][$i]['author'] = $author;
                    }
                }
            }

            // datetime
            foreach ($this->kind_of_datetime as $kind) {
                if (isset($res['items'][$i][$kind]) && ($dt = $res['items'][$i][$kind])) {
                    $res['items'][$i]['datetime'] = date('Y-m-d H:i:s', @strtotime($dt));
                }
            }

            $i++;
        }
        return $res;
    }

    private function openFeed($url)
    {
        try {
            $req = \Http::init($url, 'GET');
            $response = $req->send();
            if (strpos(\Http::getResponseHeader('http_code'), '200') === false) {
                throw new \RuntimeException(\Http::getResponseHeader('http_code'));
            }
            $this->feed = $response->getResponseBody();
        } catch (\Exception $e) {
            \AcmsLogger::error('Feedを取得できませんでした', \Common::exceptionArray($e, ['url' => $url]));
        }
    }

    private function get_attr($attr)
    {
        $regex = "@[\s'\"](.*?)\s*=\s*([^\s'\">]+|'[^']+'|\"[^\"]+\")@si";
        $res = null;
        if (preg_match_all($regex, $attr, $matches)) {
            // 0: content / 1: attribute key / 2: value
            $cnt = count($matches[0]);
            $key = $matches[1];
            $val = $matches[2];

            for ($i = 0; $i < $cnt; $i++) {
                $res[$key[$i]] = preg_replace('@^\s*[\'"](.+)[\'"]\s*$@s', '$1', $val[$i]);
            }
        }
        if (!empty($res)) {
            return $res;
        }
    }
}
