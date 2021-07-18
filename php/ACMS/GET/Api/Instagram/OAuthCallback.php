<?php

class ACMS_GET_Api_Instagram_OAuthCallback extends ACMS_GET_Api_Instagram
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        // ログインチェック
        if ( !SUID && !sessionWithAdministration(BID) ) {
            $Tpl->add('unlogin');
            return $Tpl->get();
        }

        $code  = $this->Get->get('code');

        // request tokenの保持をチェック
        if ( !empty($code) ) {

            // access tokenの取得を試行
            $API    = ACMS_Services_Instagram::establish(BID);
            $acsUrl = $API->getAcsTokenUrl(array('code' => $code));

            try {
                $req = \Http::init($acsUrl, 'post');
                $req->setRequestHeaders(array(
                    'Accept-Language: ' . HTTP_ACCEPT_LANGUAGE,
                    'User-Agent: ' . 'ablogcms/' . VERSION,
                ));
                $params = array();
                $url = parse_url($acsUrl);
                parse_str($url['query'], $params);
                $req->setPostData($params);
                $response = $req->send();
                $body = $response->getResponseBody();

                $response = json_decode($body, true);
                if ( !isset($response['access_token']) ) {
                    $Tpl->add('failed');
                } else {
                    $access = $response['access_token'];
                    $res = ACMS_Services_Instagram::insertAcsToken(BID, $access);
                    if ( empty($res) ) {
                        $Tpl->add('failed');
                    } else {
                        $Tpl->add('successed');
                    }
                }
            } catch (\Exception $e) {
                $Tpl->add('failed');
            }
        } else {
            $Tpl->add('failed');
        }

        return $Tpl->get();
    }
}
