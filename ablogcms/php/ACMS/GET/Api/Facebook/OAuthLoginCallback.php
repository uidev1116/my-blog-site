<?php

use Acms\Services\SocialLogin\Exceptions\SocialLoginException;
use Acms\Services\SocialLogin\Exceptions\SocialLoginDuplicationException;

class ACMS_GET_Api_Facebook_OAuthLoginCallback extends ACMS_GET
{
    function get()
    {
        $facebook = App::make('facebook-login');
        try {
            $loginType = $facebook->getLoginType();
            $loginBid = $facebook->getLoginBid();
            $loginUid = $facebook->getLoginUid();

            if (empty($loginType) || empty($loginBid)) {
                throw new SocialLoginException();
            }
            $facebook->setMe();

            switch ($loginType) {
                case 'login':
                    $facebook->login();
                    break;
                case 'signup':
                    $facebook->signup($loginBid);
                    break;
                case 'addition':
                    $facebook->addition($loginUid, $loginBid);
                    break;
            }
        } catch (SocialLoginException $e) {
            $facebook->loginFailed('login=failed');
        } catch (SocialLoginDuplicationException $e) {
            $facebook->loginFailed('auth=double');
        } catch (\Exception $e) {
            $facebook->loginFailed('login=failed');
        }
    }
}
