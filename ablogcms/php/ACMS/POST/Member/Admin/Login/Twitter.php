<?php

class ACMS_POST_Member_Admin_Login_Twitter extends ACMS_POST_Member_Sns_Base
{
    /**
     * アクションを設定（signin|admin-login|signup|register）
     * @return string
     */
    protected function getActionName(): string
    {
        return 'admin-login';
    }

    /**
     * 認証URLを取得
     *
     * @return string
     */
    protected function getAuthUrl(): string
    {
        $twitterApi = App::make('twitter-login');
        return $twitterApi->getAuthUrl();
    }
}
