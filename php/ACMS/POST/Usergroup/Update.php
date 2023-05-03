<?php

class ACMS_POST_Usergroup_Update extends ACMS_POST_Usergroup
{
    function post()
    {
        $Usergroup = $this->extract('usergroup');

        $Usergroup->setMethod('usergroup', 'operable', $ugid = intval($this->Get->get('ugid')) and sessionWithEnterpriseAdministration() and BID === RBID );
        $Usergroup->setMethod('name', 'required');
        $Usergroup->setMethod('name', 'double', $ugid);
        $Usergroup->setMethod('role_id', 'required');
        $Usergroup->setMethod('approval_point', 'required');

        $Usergroup->validate(new ACMS_Validator_Usergroup());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());

            //-----------
            // usergroup
            $SQL    = SQL::newUpdate('usergroup');
            foreach ( $Usergroup->listFields() as $key ) {
                if ( $key !== 'user_list' ) {
                    $SQL->addUpdate('usergroup_'.$key, $Usergroup->get($key));
                }
            }
            $SQL->addWhereOpr('usergroup_id', $ugid);
            $DB->query($SQL->get(dsn()), 'exec');

            //-----------
            // user list
            $SQL    = SQL::newDelete('usergroup_user');
            $SQL->addWhereOpr('usergroup_id', $ugid);
            $DB->query($SQL->get(dsn()), 'exec');

            foreach ($Usergroup->getArray('user_list') as $uid ) {
                $SQL    = SQL::newInsert('usergroup_user');
                $SQL->addInsert('usergroup_id', $ugid);
                $SQL->addInsert('user_id', $uid);
                $DB->query($SQL->get(dsn()), 'exec');
            }

            $this->Post->set('edit', 'update');
        }
        return $this->Post;
    }
}

