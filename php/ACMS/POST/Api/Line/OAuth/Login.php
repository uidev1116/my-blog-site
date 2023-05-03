<?php

class ACMS_POST_Api_Line_OAuth_Login extends ACMS_POST
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
        $line = App::make('line-login');
        $line->setLoginType($type, BID, $uid);
        $session = Session::handle();
        if ($state = $session->get('line_login_state')) {
            $this->state = $state;
        } else {
            $this->state = uniqueString();
            $session->set('line_login_state', uniqueString());
            $session->save();
        }

        $url = $line->getLoginUrl();
        $this->redirect($url);
    }
}
