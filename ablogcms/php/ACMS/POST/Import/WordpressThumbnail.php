<?php

class ACMS_POST_Import_WordpressThumbnail extends ACMS_POST_Import_Wordpress
{
    protected $importCid;
    protected $csvLabels;

    function init()
    {
        @set_time_limit(-1);
        $this->importType = 'WordPress Thumbnail';
        $this->uploadFiledName = 'wordpress_import_file';
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
                $id = $this->getNodeValue($xml, 'wp:post_id');
                $type = $this->getNodeValue($xml, 'wp:post_type');
                $url = $this->getNodeValue($xml, 'wp:attachment_url');

                if (empty($id) || empty($url)) {
                    continue;
                }
                if ($type !== 'attachment') {
                    continue;
                }
                while ($xml->read()) {
                    if (intval($xml->nodeType) === XMLReader::END_ELEMENT and $xml->name === 'item') {
                        if ($eids = $this->find($id)) {
                            $this->updateThumbnailImage($eids, $url);
                            $this->entryCount++;
                        }
                        break;
                    }
                }
            }
        }
        $xml->close();
        Cache::flush('field');
    }

    protected function find($id)
    {
        $sql = SQL::newSelect('field');
        $sql->setSelect('field_eid');
        $sql->addWhereOpr('field_key', 'wp_thumbnail_id');
        $sql->addWhereOpr('field_value', $id);
        $sql->addWhereOpr('field_blog_id', BID);

        return DB::query($sql->get(dsn()), 'list');
    }

    protected function updateThumbnailImage($eids, $url)
    {
        if (empty($eids) || empty($url)) {
            return;
        }
        $sql = SQL::newDelete('field');
        $sql->addWhereOpr('field_key', 'wp_thumbnail_url');
        $sql->addWhereIn('field_eid', $eids);
        $sql->addWhereOpr('field_blog_id', BID);
        DB::query($sql->get(dsn()), 'exec');

        foreach ($eids as $eid) {
            $sql = SQL::newInsert('field');
            $sql->addInsert('field_key', 'wp_thumbnail_url');
            $sql->addInsert('field_value', $url);
            $sql->addInsert('field_eid', $eid);
            $sql->addInsert('field_blog_id', BID);
            DB::query($sql->get(dsn()), 'exec');
        }
    }
}
