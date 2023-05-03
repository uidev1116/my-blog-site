<?php

class ACMS_POST_Login_Tfa_Register extends ACMS_POST_Login
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
        $tfa = $this->extract('tfa');
        $secret = $tfa->get('secret');
        $code = $tfa->get('code');

        $tfa->setMethod('code', 'required');
        $tfa->setMethod('secret', 'required');
        $tfa->setMethod('code', 'disagreement', Tfa::verifyCode($secret, $code));
        $tfa->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $iv = Common::getEncryptIv();
            $recoveryCode = Common::genPass(16);
            $this->Post->set('recoveryCode', $recoveryCode);
            $this->Post->set('auth', 'success');

            $sql = SQL::newUpdate('user');
            $sql->addUpdate('user_tfa_secret', Common::encrypt($secret, $iv));
            $sql->addUpdate('user_tfa_secret_iv', base64_encode($iv));
            $sql->addUpdate('user_tfa_recovery', acmsUserPasswordHash($recoveryCode));
            $sql->addWhereOpr('user_id', UID);
            DB::query($sql->get(dsn()), 'exec');
            ACMS_RAM::user(UID, null);
        }

        return $this->Post;
    }
}
