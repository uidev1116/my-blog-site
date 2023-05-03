<?php

class ACMS_POST_Api_Facebook_OAuth_Login extends ACMS_POST
{
    function post()
    {
        $type = $this->Post->get('type');
        $uid = null;
        if (empty($type)) {
            return false;
        }
        if ($type === 'addition') {
            $uid = UID;
        }
        $facebook = App::make('facebook-login');
        $facebook->setLoginType($type, BID, $uid);

        $blogUrl = acmsLink(array('bid' => BID), false);
        $url = $facebook->getLoginUrl($blogUrl . 'callback/signin/facebook.html');
        $this->redirect($url);
    }
}
