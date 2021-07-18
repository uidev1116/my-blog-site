<?php

class ACMS_GET_Api_Twitter_AdminOAuth extends ACMS_GET_Api_Twitter
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $key    = config('twitter_consumer_key');
        $secret = config('twitter_consumer_secret');

        // access tokenの保持をチェック
        $accessTokens = ACMS_Services_Twitter::loadAcsToken(BID);
        if ( $accessTokens && count($accessTokens) == 2 ) {

            $API = ACMS_Services_Twitter::establish(BID);

            if ( !!($API->httpRequest('account/verify_credentials.json', array(), 'GET')) ) {
                $json    = $API->Response->getResponseBody();
                $json    = json_decode($json);

                $vars   = array(
                    'id'                => $json->id,
                    'screen_name'       => $json->screen_name,
                    'user_name'         => $json->name,
                    'statuses_count'    => $json->statuses_count,
                    'followers_count'   => $json->followers_count,
                    'friends_count'     => $json->friends_count,
                    'limit'             => $API->Response->getResponseHeader('x-rate-limit-limit'),
                    'remaining'         => $API->Response->getResponseHeader('x-rate-limit-remaining'),
                    'reset'             => date('H:i:s', $API->Response->getResponseHeader('x-rate-limit-reset')),
                );
                $Tpl->add('Auth', $vars);
            } else {
                $Tpl->add('failed');
            }
        } elseif ( !empty($key) && !empty($secret) ) {

            $API    = ACMS_Services_Twitter::establish(BID, 'none');
            $token  = $API->getReqToken();
            $url    = $API->getAuthUrl();

            $vars   = array(
                'oauth_url'             => $url,
                'oauth_token'           => $token['oauth_token'],
                'oauth_token_secret'    => $token['oauth_token_secret'],
            );
            $Tpl->add('notAuth', $vars);
        } else {
            $Tpl->add('notFoundKeys');
        }

        return $Tpl->get();
    }
}
