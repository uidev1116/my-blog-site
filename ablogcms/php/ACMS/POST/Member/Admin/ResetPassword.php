<?php

class ACMS_POST_Member_Admin_ResetPassword extends ACMS_POST_Member_ResetPassword
{
    /**
     * 認証URLを取得
     *
     * @param string $token
     * @param array $data
     * @param int $lifetime
     * @return string
     */
    protected function getAuthUrl(string $token, array $data, int $lifetime): string
    {
        return $this->createAuthUrl([
            'bid' => BID,
            'admin-reset-password-auth' => true,
        ], $token, $data, $lifetime);
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
