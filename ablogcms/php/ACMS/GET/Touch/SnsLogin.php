<?php

use Acms\Services\Facades\Login;

class ACMS_GET_Touch_SnsLogin extends ACMS_GET
{
    function get()
    {
        if (config('snslogin') !== 'on') {
            return '';
        }
        return $this->tpl;
    }
}
