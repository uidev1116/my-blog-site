<?php

class ACMS_POST_Usergroup_Insert extends ACMS_POST_Usergroup
{
    function post()
    {
        $Usergroup = $this->extract('usergroup');
        $Usergroup->setMethod('name', 'required');
        $Usergroup->setMethod('name', 'double');
        $Usergroup->setMethod('role_id', 'required');
        $Usergroup->setMethod('usergroup', 'operable', sessionWithEnterpriseAdministration() and BID === RBID );

        $Usergroup->validate(new ACMS_Validator_Usergroup());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());

            //------
            // ugid
            $ugid   = $DB->query(SQL::nextval('usergroup_id', dsn()), 'seq');

            //-----------
            // usergroup
            $SQL    = SQL::newInsert('usergroup');
            $SQL->addInsert('usergroup_id', $ugid);
            foreach ( $Usergroup->listFields() as $key ) {
                if ( $key !== 'user_list' ) {
                    $SQL->addInsert('usergroup_'.$key, $Usergroup->get($key));
                }
            }
            $DB->query($SQL->get(dsn()), 'exec');

            //-----------
            // user list
            foreach ($Usergroup->getArray('user_list') as $uid ) {
                $SQL    = SQL::newInsert('usergroup_user');
                $SQL->addInsert('usergroup_id', $ugid);
                $SQL->addInsert('user_id', $uid);
                $DB->query($SQL->get(dsn()), 'exec');
            }
            $this->Post->set('edit', 'insert');
        }
        return $this->Post;
    }
}