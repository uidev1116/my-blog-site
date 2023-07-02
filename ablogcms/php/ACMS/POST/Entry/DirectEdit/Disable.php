<?php

class ACMS_POST_Entry_DirectEdit_Disable extends ACMS_POST
{
    public function post()
    {
        if (SUID) {
            $session =& Field::singleton('session');
            $session->set('entry_direct_edit', 'disable');
            $this->redirect(REQUEST_URL);
        }
    }
}
