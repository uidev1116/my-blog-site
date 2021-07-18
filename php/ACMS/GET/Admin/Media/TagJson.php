<?php

use Acms\Services\Facades\Common;
use Acms\Services\Facades\Media;

class ACMS_GET_Admin_Media_TagJson extends ACMS_GET_Admin_Media_ListJson
{
    public function get()
    {
        $sql = $this->buildSql();
        $tag = Media::getMediaTagList($sql);
        Common::responseJson($tag);
    }
}
