<?php

class ACMS_POST_Usergroup_Delete extends ACMS_POST
{
    function post()
    {
        $this->Post->setMethod('usergroup', 'operable', 
            ($ugid = intval($this->Get->get('ugid'))) and sessionWithEnterpriseAdministration() and BID === RBID
        );
        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());

            //--------
            // delete
            $SQL    = SQL::newDelete('usergroup');
            $SQL->addWhereOpr('usergroup_id', $ugid);
            $DB->query($SQL->get(dsn()), 'exec');
            $this->Post->set('edit', 'delete');

            $SQL    = SQL::newDelete('usergroup_user');
            $SQL->addWhereOpr('usergroup_id', $ugid);
            $DB->query($SQL->get(dsn()), 'exec');
        }

        return $this->Post;
    }
}
