<?php

class ACMS_GET_Api_Bing_ImageSearch extends ACMS_GET_Api_Bing
{
    var $_scope = array(
        'bid'       => 'global',
        'keyword'   => 'global',
        'page'      => 'global'
    );

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $base   = config('api_bing_image_search_base_url');

        if( version_compare(PHP_VERSION, '5.0.0', '<') ) {
            $Tpl->add('legacyVersion');
            return $Tpl->get();
        }

        $page   = $this->page ? $this->page : 1;

        $accountKey = config('bing_search_api_key');
        $auth       = base64_encode("$accountKey:$accountKey");
        $domain     = ACMS_RAM::blogDomain($this->bid);

        $params = array_clean(array(
            '$format'       => 'json',
            'Query'         => '\''.urlencode($this->keyword.' site:'.$domain).'\'',
            '$top'          => config('api_bing_image_search_limit'),
            '$skip'         => ($page === 1) ? 0 : (config('api_bing_image_search_limit') * ($page - 1)) + 1,
            'Adult'         => '\''.config('api_bing_image_search_adult').'\'',
        ));

        if ( empty($accountKey) ) {
            $Tpl->add('notAccountKey');
            return $Tpl->get();
        }

        if ( empty($params['Query']) ) {
            $Tpl->add('notQuery');
            return $Tpl->get();
        }

        $q = '';
        foreach ( $params as $key => $val ) {
            $q .= $key.'='.$val.'&';
        }
        $q = substr($q, 0, -1);
        $url    = $base.'?'.$q;

        $data = array(
            'http'  => array(
                'request_fulluri' => true,
                'ignore_errors'   => true,
                'header'          => "Authorization: Basic $auth",
            )
        );

        $context   = stream_context_create($data);
        $rawjson   = Storage::get($url, 0, $context);
        
        $pos = strpos($http_response_header[0], '200');
        if ($pos === false) {
            $Tpl->add('unavailable', array('message' => $rawjson));
        } else if ( $json = json_decode($rawjson) ) {
            $results = $json->d->results;

            if ( is_array($results) ) {
                foreach ( $results as $row ) {
                    $entry   = array(
                        'title'     => $row->Title,
                        'mediaUrl'  => $row->MediaUrl,
                        'displayUrl' => $row->DisplayUrl,
                        'sourceUrl' => $row->SourceUrl,
                        'fileSize'  => $row->FileSize,

                        'height'    => $row->Height,
                        'width'     => $row->Width,

                        'thumbUrl'      => $row->Thumbnail->MediaUrl,
                        'thumbHeight'   => $row->Thumbnail->Height,
                        'thumbWidth'    => $row->Thumbnail->Width,
                    );
                    $Tpl->add('result:loop', $entry);
                }

                $vars = array();
                if ( isset($json->d->__next) ) {
                    $vars = array(
                        'nextUrl'   => acmsLink(array(
                            'page'      => $page + 1,
                            'keyword'   => $this->keyword,
                            'bid'       => $this->bid,
                        )),
                        'nextPage'  => $page + 1,
                    );
                }
                if ( $page > 1 ) {
                    $vars += array(
                        'prevUrl'   => acmsLink(array(
                            'page'      => $page - 1,
                            'keyword'   => $this->keyword,
                            'bid'       => $this->bid,
                        )),
                        'prevPage'  => $page - 1,
                    );
                }
                $Tpl->add(null, $vars);
            } else {
                $Tpl->add('notFound');
            }
        }
        return $Tpl->get();
    }
}
