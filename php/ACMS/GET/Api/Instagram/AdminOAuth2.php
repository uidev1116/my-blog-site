<?php

class ACMS_GET_Api_Instagram_AdminOAuth2 extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        if (!isFacebookLoginAvailable()) {
            $Tpl->add('notFoundKeys');
            return $Tpl->get();
        }

        $instagram = App::make('instagram-login');
        $key      = config('instagram_graph_client_id');
        $secret   = config('instagram_graph_client_secret');
        $redirect = config('instagram_graph_client_redirect');

        try {
            // access tokenの保持をチェック
            if ( !!($accessToken = $instagram->loadAccessToken(BID)) ) {
                $instagram->setAccessToken($accessToken);
                $instagram->setMe();

                if ( !!($id = $instagram->getId()) ) {
                    $vars   = array(
                        'id'            => $id,
                        'user_name'     => $instagram->getName(),
                        'full_name'     => $instagram->getName(), //todo
                    );
                    $Tpl->add('Auth', $vars);
                } else {
                    $Tpl->add('failed');
                }

            } else if ( !empty($key) && !empty($secret) && !empty($redirect) ) {
                $vars   = array(
                    'oauth_url' => '',
                );
                $Tpl->add('notAuth', $vars);
            } else {
                $Tpl->add('notFoundKeys');
            }
        } catch (\Exception $e) {
            $Tpl->add('failed');
        }

        return $Tpl->get();
    }
}
