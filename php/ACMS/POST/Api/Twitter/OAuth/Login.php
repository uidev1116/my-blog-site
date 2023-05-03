<?php

class ACMS_POST_Api_Twitter_OAuth_Login extends ACMS_POST_Api_Twitter
{
    function post()
    {
        $type       = $this->Post->get('type');
        if ( empty($type) ) return false;

        $session = Session::handle();
        $Config = Config::loadBlogConfigSet(BID);

        // twitter
        $Tw = new Services_Twitter(
            $Config->get('twitter_sns_login_consumer_key'),
            $Config->get('twitter_sns_login_consumer_secret')
        );
        $token_secret_callback = array_chunk($Tw->getReqToken(array(
            'oauth_callback' => 'oob'
        )), 3);
        list($token, $secret, $callback) = $token_secret_callback[0];

        $session->set('tw_token', $token);
        $session->set('tw_secret', $secret);
        $session->set('tw_blog_id', BID);
        $session->set('tw_request', $type);

        if ( $type === 'addition' ) {
            $session->set('tw_user_id', UID);
        }
        $session->save();

        $this->redirect($Tw->getAuthUrl());
    }
}
