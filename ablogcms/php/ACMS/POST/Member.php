<?php

class ACMS_POST_Member extends ACMS_POST
{
    /**
     * キャッシュ削除をオフ
     *
     * @var bool
     */
    var $isCacheDelete = false;

    /**
     * CSRF対策
     *
     * @var bool
     */
    protected $isCSRF = true;

    /**
     * 二重送信対策
     *
     * @var bool
     */
    protected $checkDoubleSubmit = true;

}
