<?php

class ACMS_POST_Member_Sns_Google_Signin extends ACMS_POST_Member_Sns_Base
{
    /**
     * アクションを設定（signin|admin-login|signup|register）
     * @return string
     */
    protected function getActionName(): string
    {
        return 'signin';
    }

    /**
     * 認証URLを取得
     *
     * @return string
     */
    protected function getAuthUrl(): string
    {
        $googleApi = App::make('google-login');
        return $googleApi->getAuthUrl();
    }
}
