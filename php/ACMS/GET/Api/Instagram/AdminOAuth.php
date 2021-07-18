<?php

class ACMS_GET_Api_Instagram_AdminOAuth extends ACMS_GET_Api_Instagram
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $key      = config('instagram_client_id');
        $secret   = config('instagram_client_secret');
        $redirect = config('instagram_client_redirect');

        // access tokenの保持をチェック
        if ( !!(ACMS_Services_Instagram::loadAcsToken(BID)) ) {

            $API = ACMS_Services_Instagram::establish(BID);

            if ( !!($API->httpRequest('/users/self', array(), 'GET')) ) {
                $json   = $API->Response->getResponseBody();
                $data   = json_decode($json, true);
                $data   = $data['data'];

                $vars   = array(
                    'id'            => $data['id'],
                    'user_name'     => $data['username'],
                    'full_name'     => $data['full_name'],
                );

                $Tpl->add('Auth', $vars);
            } else {
                $Tpl->add('failed');
            }

        } elseif ( !empty($key) && !empty($secret) && !empty($redirect) ) {

            $API    = ACMS_Services_Instagram::establish(BID);
            $url    = $API->getAuthUrl();

            $vars   = array(
                'oauth_url'             => $url,
            );
            $Tpl->add('notAuth', $vars);
        } else {
            $Tpl->add('notFoundKeys');
        }

        return $Tpl->get();
    }
}
