<?php

class ACMS_GET_Api_Google extends ACMS_GET_Api
{
    /**
     * Google API Client Library
     *
     * @return Google_Client
     */
    function getGoogleClient()
    {
        $this->redirect_uri = BASE_URL.'callback/signin/google.html';

        $Config         = Config::loadBlogConfigSet(BID);
        $client_id      = $Config->get('google_login_client_id');
        $secret_key     = $Config->get('google_login_secret');

        $config = new Google_Config();
        $config->setClassConfig('Google_Cache_File', array(
            'directory' => SCRIPT_DIR.ARCHIVES_DIR,
        ));

        $Client = new Google_Client($config);
        $Client->setClientId($client_id);
        $Client->setClientSecret($secret_key);
        $Client->setRedirectUri($this->redirect_uri);
        $Client->addScope("email");
        $Client->addScope("profile");

        return $Client;
    }

    /**
     * google認証からユーザー情報を抜き出し
     *
     * @param Google_Client
     * @return array
     */
    function extractAccountData($data)
    {
        return array(
            'bid'           => $this->auth_bid,
            'code'          => $data->id,
            'name'          => $data->name,
            'email'         => $data->email,
            'oauth_type'    => 'user_google_id',
            'oauth_id'      => $data->id,
            'icon'          => '',
        );
    }
}
