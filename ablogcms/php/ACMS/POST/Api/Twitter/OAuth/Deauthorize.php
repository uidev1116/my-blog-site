<?php

class ACMS_POST_Api_Twitter_OAuth_Deauthorize extends ACMS_POST_Api_Twitter
{
    function post()
    {
        if ( !SUID || !sessionWithSubscription() ) { return true; }
        if ( UID <> SUID && !sessionWithAdministration() ) { return true; }

        $session = Session::handle();
        $session->delete('tw_token');
        $session->delete('tw_secret');

        $DB = DB::singleton(dsn());
        $SQL = SQL::newUpdate('user');
        $SQL->addUpdate('user_twitter_id', '');
        $SQL->addWhereOpr('user_id', UID);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::user(UID, null);

        $this->redirect(acmsLink(array(
            'bid'           => BID,
            'uid'           => UID,
            'admin'         => 'user_edit',
        )));
    }
}
