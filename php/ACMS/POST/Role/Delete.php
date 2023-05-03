<?php

class ACMS_POST_Role_Delete extends ACMS_POST
{
    function post()
    {
        $this->Post->setMethod('role', 'operable', 
            ($rid = intval($this->Get->get('rid'))) and sessionWithEnterpriseAdministration() and BID === RBID
        );
        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());

            //--------
            // delete
            $SQL    = SQL::newDelete('role');
            $SQL->addWhereOpr('role_id', $rid);
            $DB->query($SQL->get(dsn()), 'exec');
            $this->Post->set('edit', 'delete');

            $SQL    = SQL::newDelete('role_blog');
            $SQL->addWhereOpr('role_id', $rid);
            $DB->query($SQL->get(dsn()), 'exec');
        }

        return $this->Post;
    }
}
