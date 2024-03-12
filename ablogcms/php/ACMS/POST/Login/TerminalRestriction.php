<?php

class ACMS_POST_Login_TerminalRestriction extends ACMS_POST
{
    function post()
    {
        $status = $this->Post->get('status', 'denial');
        $hash   = sha1($status . UA);

        acmsSetCookie('acms_config_login_terminal_restriction', $hash, null, '/');

        AcmsLogger::info('端末の拒否・許可設定をしました', [
            'status' => $status,
        ]);

        $this->redirect(REQUEST_URL);
    }
}
