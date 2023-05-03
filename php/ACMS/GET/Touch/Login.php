<?php

use Acms\Services\Facades\Preview;

class ACMS_GET_Touch_Login extends ACMS_GET
{
    public function get()
    {
        return (!!ACMS_SID && !RVID && !Preview::isPreviewMode()) ? $this->tpl : false;
    }
}
