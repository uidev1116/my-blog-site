<?php

class ACMS_POST_Member_Admin_Login extends ACMS_POST_Member_Signin
{
    /**
     * 権限の限定
     *
     * @return array
     */
    protected function limitedAuthority(): array
    {
        return Login::getAdminLoginAuth();
    }

    /**
     * アクセス制限のチェック
     *
     * @return bool
     */
    protected function accessRestricted(): bool
    {
        return Login::accessRestricted(true);
    }

    /**
     * パスワードを使った認証かチェック
     *
     * @return bool
     */
    protected function passwordAuth(): bool
    {
        return config('email-auth-login') !== 'on';
    }
}
