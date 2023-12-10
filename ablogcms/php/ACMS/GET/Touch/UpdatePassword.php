<?php

class ACMS_GET_Touch_UpdatePassword extends ACMS_GET
{
    function get()
    {
        if (!SUID) {
            return '';
        }
        if (in_array(ACMS_RAM::userAuth(SUID), Login::getSinginAuth())) {
            if (config('email-auth-signin') !== 'on') {
                // パスワードなしのメール認証によるサインイン設定がオフの場合、パスワード設定を表示
                return $this->tpl;
            }
        } else {
            if (config('email-auth-login') !== 'on') {
                // パスワードなしのメール認証による管理ログイン設定がオフの場合、パスワード設定を表示
                return $this->tpl;
            }
        }
        return '';
    }
}
