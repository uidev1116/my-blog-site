<?php

use Acms\Services\Facades\Common;

class ACMS_POST_Login_Check extends ACMS_POST
{
    /**
     * @var bool
     */
    public $isCacheDelete = false;

    /**
     * @var bool
     */
    protected $isCSRF = false;

    /**
     * @var bool
     */
    protected $checkDoubleSubmit = false;

    function post()
    {
        Common::responseJson([
            'isLogin' => !!SUID,
        ]);
    }
}
