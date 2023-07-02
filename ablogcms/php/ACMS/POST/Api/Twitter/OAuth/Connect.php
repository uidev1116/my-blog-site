<?php

class ACMS_POST_Api_Twitter_OAuth_Connect extends ACMS_POST_Api_Twitter
{
    function post()
    {
        $Twitter = $this->extract('twitter');

        // リクエストトークンは，ACMS_Api_Twitter_AdminOAuthで発行済み
        $token  = $Twitter->get('oauth_token');
        $secret = $Twitter->get('oauth_token_secret');

        // 既存のトークンを削除
        ACMS_Services_Twitter::deleteOAuthToken(BID);

        // DBにリクエストトークンをメモ
        ACMS_Services_Twitter::insertReqToken(BID, $token, $secret);

        $this->redirect($Twitter->get('oauth_url'));
    }
}
