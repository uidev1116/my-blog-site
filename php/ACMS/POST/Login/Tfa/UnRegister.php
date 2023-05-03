<?php

class ACMS_POST_Login_Tfa_UnRegister extends ACMS_POST_Login
{
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

    /**
     * @return false|Field|string
     */
    function post()
    {
        if (!Tfa::checkAuthority()) {
            return '';
        }
        $sql = SQL::newUpdate('user');
        $sql->addUpdate('user_tfa_secret', null);
        $sql->addUpdate('user_tfa_secret_iv', null);
        $sql->addUpdate('user_tfa_recovery', null);
        $sql->addWhereOpr('user_id', UID);
        DB::query($sql->get(dsn()), 'exec');
        ACMS_RAM::user(UID, null);

        return $this->Post;
    }
}
