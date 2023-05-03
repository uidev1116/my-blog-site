<?php

class ACMS_POST_Api_Google_OAuth_Login extends ACMS_POST_Api_Google
{
    function post()
    {
        $type       = $this->Post->get('type');
        if ( empty($type) ) return false;

        $session = Session::handle();
        $Config = Config::loadBlogConfigSet(BID);

        $client_id      = $Config->get('google_login_client_id');
        $secret_key     = $Config->get('google_login_secret');
        $redirect_uri   = BASE_URL.'callback/signin/google.html';

        if ( empty($client_id) || empty($secret_key) ) {
            return false;
        }

        $config = new Google_Config();
        $config->setClassConfig('Google_Cache_File', array(
            'directory' => SCRIPT_DIR.ARCHIVES_DIR,
        ));
        $Client = new Google_Client($config);
        $Client->setClientId($client_id);
        $Client->setClientSecret($secret_key);
        $Client->setRedirectUri($redirect_uri);
        $Client->addScope("email");
        $Client->addScope("profile");

        $session->set('google_blog_id', BID);
        $session->set('google_request', $type);
        if ( $type === 'addition' ) {
            $session->set('google_user_id', UID);
        }
        $session->save();

        $this->redirect($Client->createAuthUrl());
    }
}
