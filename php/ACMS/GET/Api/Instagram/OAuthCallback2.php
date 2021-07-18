<?php


use Acms\Services\SocialLogin\Exceptions\SocialLoginException;
use Acms\Services\SocialLogin\Exceptions\SocialLoginDuplicationException;

class ACMS_GET_Api_Instagram_OAuthCallback2 extends ACMS_GET
{
    function get()
    {
        $instagram = App::make('instagram-login');
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        // ログインチェック
        if ( !SUID && !sessionWithAdministration(BID) ) {
            $Tpl->add('unlogin');
            return $Tpl->get();
        }

        try {
            $accessToken = $instagram->getAccessToken();
            if ( !$accessToken ) {
                $Tpl->add('failed');
            } else {
                $res = $instagram->insertOAuthToken(BID, $accessToken);
                if ( empty($res) ) {
                    $Tpl->add('failed');
                } else {
                    $Tpl->add('successed');
                }
            }
        } catch (SocialLoginException $e) {
            $instagram->loginFailed('login=failed');
            $Tpl->add('failed');
        } catch (SocialLoginDuplicationException $e) {
            $instagram->loginFailed('auth=double');
            $Tpl->add('failed');
        } catch (\Exception $e) {
            $instagram->loginFailed('login=failed');
            $Tpl->add('failed');
        }
        return $Tpl->get();
    }
}
