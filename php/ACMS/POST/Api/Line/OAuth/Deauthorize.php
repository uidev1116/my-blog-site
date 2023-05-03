<?php

class ACMS_POST_Api_Line_OAuth_Deauthorize extends ACMS_POST
{
    function post()
    {
        if (!SUID || !sessionWithSubscription()) {
            return true;
        }
        if (UID <> SUID && !sessionWithAdministration()) {
            return true;
        }
        $DB = DB::singleton(dsn());
        $SQL = SQL::newUpdate('user');
        $SQL->addUpdate('user_line_id', '');
        $SQL->addWhereOpr('user_id', UID);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::user(UID, null);

        $this->redirect(acmsLink(array(
            'bid' => BID,
            'uid' => UID,
            'admin' => 'user_edit',
        )));
    }
}
