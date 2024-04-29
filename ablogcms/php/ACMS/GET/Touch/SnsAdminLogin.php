<?php

use Acms\Services\Facades\Login;

class ACMS_GET_Touch_SnsAdminLogin extends ACMS_GET
{
    function get()
    {
        if (config('snslogin') !== 'on') {
            return '';
        }
        $auth = config('snslogin_auth');
        if (in_array($auth, Login::getAdminLoginAuth(), true)) {
            return $this->tpl;
        }
        return '';
    }
}
