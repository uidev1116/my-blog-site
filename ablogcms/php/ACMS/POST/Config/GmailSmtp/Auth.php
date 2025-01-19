<?php

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Session;
use Acms\Services\Mailer\Transport\GoogleApi;

class ACMS_POST_Config_GmailSmtp_Auth extends ACMS_POST
{
    public function post()
    {
        if (!sessionWithAdministration()) {
            $this->addError('不正な操作です。');
            return $this->Post;
        }
        $api = Application::make('mailer.google.smtp.api');
        assert($api instanceof GoogleApi);
        $setid = intval($this->Get->get('setid'));
        if (empty($setid)) {
            $setid = null;
        }

        $session = Session::handle();
        $session->set('mailer.google.smtp.api.setid', $setid);
        $session->save();

        $api->init(BID, $setid);
        $client = $api->getClient();
        $this->redirect($client->createAuthUrl());
    }
}
