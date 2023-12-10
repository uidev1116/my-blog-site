<?php

use Acms\Services\Facades\Storage;

class ACMS_POST_Import_Wordpress extends ACMS_POST_Import
{
    protected $importCid;
    protected $csvLabels;

    function init()
    {
        @set_time_limit(-1);
        $this->importType = 'WordPress';
        $this->uploadFiledName = 'wordpress_import_file';
        $this->importCid = intval($this->Post->get('category_id'));
        if (intval($this->importCid) == 0) {
            $this->importCid = null;
        } else {
            $this->Post->set('categoryName', ACMS_RAM::categoryName($this->importCid) . 'カテゴリー');
        }
    }

    function import()
    {
        $this->httpFile->validateFormat(array('text/xml', 'application/xml'));
        $path = $this->httpFile->getPath();
        $data = Storage::get($path);
        $data = Storage::removeIllegalCharacters($data); // 不正な文字コードを削除
        $this->validateXml($data);

        $xml = new XMLReader();
        $xml->xml($data);

        while ($xml->read()) {
            if ($xml->name === 'item' and intval($xml->nodeType) === XMLReader::ELEMENT) {
                $title = $this->getNodeValue($xml, 'title');
                $content = $this->getNodeValue($xml, 'content:encoded');
                $date = $this->getNodeValue($xml, 'wp:post_date');
                $status = $this->getNodeValue($xml, 'wp:status');
                $type = $this->getNodeValue($xml, 'wp:post_type');

                if ($type !== 'post' && $type !== 'page') {
                    continue; // 投稿タイプが「投稿」「固定ページ」でなかった場合はスキップ
                }

                $tags = array();
                $fields = array();
                $status = $this->convertStatus($status);

                // 本文からサムネイル画像のパスを抜き出し
                if (preg_match('/<\s*img(?:"[^"]*"|\'[^\']*\'|[^\'">])*\s+src\s*=\s*("[^"]+"|\'[^\']+\'|[^\'"\s>]+)(?:"[^"]*"|\'[^\']*\'|[^\'">])*>/ui', $content, $matches)) {
                    if (isset($matches[1])) {
                        $fields[] = array(
                            'key' => 'wp_thumbnail_url',
                            'value' => trim($matches[1], '"\''),
                        );
                    }
                }

                while ($xml->read()) {
                    // エントリーを作成
                    if (intval($xml->nodeType) === XMLReader::END_ELEMENT and $xml->name === 'item') {
                        //insert
                        if (empty($title)) {
                            $title = '空のタイトル';
                        }
                        if (empty($date)) {
                            $date = date('Y-m-d H:i:s', REQUEST_TIME);
                        }
                        if (empty($status)) {
                            $status = 'close';
                        }
                        $entry = array(
                            'title'     => $title,
                            'content'   => $this->buildMoreContent($content),
                            'date'      => $date,
                            'status'    => $status,
                            'tags'      => $tags,
                            'fields'    => $fields,
                        );
                        $this->insertEntry($entry);
                        break;
                    }
                    // タグ
                    if ($xml->name === 'category' and $xml->getAttribute('domain') === 'post_tag' and intval($xml->nodeType) === XMLReader::ELEMENT) {
                        $xml->read();
                        $tags[] = $xml->value;
                    }
                    // カスタムフィールド
                    if ($xml->name === 'wp:postmeta' and intval($xml->nodeType) === XMLReader::ELEMENT) {
                        $key    = $this->getNodeValue($xml, 'wp:meta_key');
                        $value  = $this->getNodeValue($xml, 'wp:meta_value');
                        if (!preg_match('/^_.*/', $key)) {
                            $fields[] = array(
                                'key' => $key,
                                'value' => $value,
                            );
                        } else if ($key === '_thumbnail_id') {
                            // アイキャッチの画像IDを保存
                            $fields[] = array(
                                'key' => 'wp_thumbnail_id',
                                'value' => $value,
                            );
                        }
                    }
                }
            }
        }
        $xml->close();
    }

    function convertStatus($status)
    {
        switch ($status) {
            case 'publish':
                $status = 'open';
                break;
            case 'draft':
                $status = 'draft';
                break;
            default:
                $status = 'close';
        }
        return $status;
    }

    function getNodeValue(&$xml, $node)
    {
        $nodeValue = '';
        while ($xml->read()) {
            if ($xml->name === $node) {
                $xml->read();
                $nodeValue = $xml->value;
                break;
            }
            if (intval($xml->nodeType) === XMLReader::END_ELEMENT and $xml->name === $node) {
                break;
            }
        }
        return $nodeValue;
    }

    function buildMoreContent($content)
    {
        return explode('<!--more-->', $content, 2);
    }

    function validateXml($data)
    {
        $reader = new XMLReader();
        $reader->xml($data);
        $reader->setParserProperty(XMLReader::VALIDATE, true);
        if (!$reader->isValid()) {
            $reader->close();
            throw new RuntimeException('XMLファイルが正しくありません。または正しいエクスポートファイルではありません。');
        }
        $reader->close();
    }
}
