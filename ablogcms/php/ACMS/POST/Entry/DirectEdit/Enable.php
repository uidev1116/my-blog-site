<?php

class ACMS_POST_Entry_DirectEdit_Enable extends ACMS_POST
{
    public function post()
    {
        if (SUID) {
            $session =& Field::singleton('session');
            $session->set('entry_direct_edit', 'enable');

            AcmsLogger::info('ダイレクト編集を有効化させました');

            $this->redirect(REQUEST_URL);
        }
        return $this->Post;
    }
}
