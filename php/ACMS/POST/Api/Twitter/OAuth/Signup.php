<?php

class ACMS_POST_Api_Twitter_OAuth_Signup extends ACMS_POST_Api_Twitter
{
    function post()
    {
        $session = Session::handle();
        $Config = Config::loadBlogConfigSet(RBID);

        // twitter
        $Tw = new Services_Twitter(
            $Config->get('twitter_sns_login_consumer_key'),
            $Config->get('twitter_sns_login_consumer_secret')
        );
        $token_secret_callback = array_chunk($Tw->getReqToken(), 3);
        list($token, $secret, $callback) = $token_secret_callback[0];

        $session->set('tw_token', $token);
        $session->set('tw_secret', $secret);
        $session->set('tw_blog_id', BID);
        $session->set('tw_request', 'signup');
        $session->save();

        $this->redirect($Tw->getAuthUrl());
    }
}
