<?php

class ACMS_POST_Entry_DirectEdit_Disable extends ACMS_POST
{
    public function post()
    {
        if (SUID) {
            $session =& Field::singleton('session');
            $session->set('entry_direct_edit', 'disable');

            AcmsLogger::info('ダイレクト編集を無効化させました');

            $this->redirect(REQUEST_URL);
        }
    }
}
