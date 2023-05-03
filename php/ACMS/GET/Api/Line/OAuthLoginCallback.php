<?php

use Acms\Services\SocialLogin\Exceptions\SocialLoginException;
use Acms\Services\SocialLogin\Exceptions\SocialLoginDuplicationException;

class ACMS_GET_Api_Line_OAuthLoginCallback extends ACMS_GET
{
    function get()
    {
        $line = App::make('line-login');
        try {
            $loginType = $line->getLoginType();
            $loginBid = $line->getLoginBid();
            $loginUid = $line->getLoginUid();

            if (empty($loginType) || empty($loginBid)) {
                throw new SocialLoginException();
            }
            if ($line->getState() !== $this->Get->get('state')) {
                throw new SocialLoginException();
            }
            $accessToken = $line->getAccessToken($this->Get->get('code'));
            $line->setMe($accessToken);

            switch ($loginType) {
                case 'login':
                    $line->login();
                    break;
                case 'signup':
                    $line->signup($loginBid);
                    break;
                case 'addition':
                    $line->addition($loginUid, $loginBid);
                    break;
            }
        } catch (SocialLoginException $e) {
            $line->loginFailed('login=failed');
        } catch (SocialLoginDuplicationException $e) {
            $line->loginFailed('auth=double');
        } catch (\Exception $e) {
            $line->loginFailed('login=failed');
        }
    }
}
