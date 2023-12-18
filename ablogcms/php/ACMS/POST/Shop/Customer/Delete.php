<?php

class ACMS_POST_Shop_Customer_Delete extends ACMS_POST_Shop
{   
    function post()
    {
        if ( !sessionWithAdministration() ) return die();

        $DB = DB::singleton(dsn());
        $SQL = SQL::newDelete('user');
        $SQL->addWhereOpr('user_id', UID);
        $SQL->addWhereOpr('user_auth', 'subscriber');
		$DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::user(UID, null);

        return $this->Post;
    }
}