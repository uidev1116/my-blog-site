<?php

use Acms\Services\Facades\Preview;

class ACMS_GET_Touch_Unlogin extends ACMS_GET
{
    public function get()
    {
        return !ACMS_SID || Preview::isPreviewMode() ? $this->tpl : false;
    }
}
