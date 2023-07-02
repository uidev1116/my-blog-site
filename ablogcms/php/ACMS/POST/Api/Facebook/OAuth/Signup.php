<?php

class ACMS_POST_Api_Facebook_OAuth_Signup extends ACMS_POST
{
    function post()
    {
        $session = Session::handle();
        $session->set('fb_blog_id', BID);
        $session->set('fb_request', 'signup');
        $session->save();

        $facebook = App::make('facebook-login');
        $blogUrl = acmsLink(array('bid' => BID), false);
        $url = $facebook->getLoginUrl($blogUrl . 'callback/signin/facebook.html');
        $this->redirect($url);
    }
}
