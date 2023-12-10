<?php

class ACMS_GET_Member_Sns_Message extends ACMS_GET
{
    public function get()
    {
        $session = Session::handle();
        $tpl = new Template($this->tpl, new ACMS_Corrector());

        if ($session->get('oauth-register') === 'success') {
            $tpl->add('oauth-register-success');
        }
        if ($session->get('oauth-register') === 'error') {
            $tpl->add('oauth-register-error');
        }
        if ($session->get('oauth-unregister') === 'success') {
            $tpl->add('oauth-unregister-success');
        }
        if ($session->get('oauth-signin') === 'error') {
            $tpl->add('oauth-signin-error');
        }
        if ($session->get('oauth-signup') === 'error') {
            $tpl->add('oauth-signup-error');
        }

        $session->delete('oauth-register');
        $session->delete('oauth-unregister');
        $session->delete('oauth-signin');
        $session->delete('oauth-signup');
        $session->save();

        return $tpl->get();
    }
}
