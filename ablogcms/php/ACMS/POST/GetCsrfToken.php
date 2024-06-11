<?php

use Acms\Services\Facades\Common;

class ACMS_POST_GetCsrfToken extends ACMS_POST
{
    public $isCacheDelete  = false;

    protected $isCSRF = false;

    public function post()
    {
        $token = '';
        if (SUID) { // @phpstan-ignore-line
            $token = Common::createCsrfToken();
        }
        Common::responseJson([
            'token' => $token,
        ]);
    }
}
