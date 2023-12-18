<?php

class ACMS_POST_Member_Sns_Twitter_Signin extends ACMS_POST_Member_Sns_Base
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
        $twitterApi = App::make('twitter-login');
        return $twitterApi->getAuthUrl();
    }
}
