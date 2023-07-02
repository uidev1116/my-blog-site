<?php

class ACMS_POST_Role_Update extends ACMS_POST
{
    function post()
    {
        $Role = $this->extract('role');
        $Role->setMethod('name', 'required');
        $Role->setMethod('role', 'operable', sessionWithEnterpriseAdministration() and $rid = intval($this->Get->get('rid')) and BID === 1 );

        $Role->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());

            //-----------
            // role
            $SQL    = SQL::newUpdate('role');
            foreach ( $Role->listFields() as $key ) {
                if ( $key !== 'blog_list' ) {
                    $SQL->addUpdate('role_'.$key, $Role->get($key));
                }
            }
            $SQL->addWhereOpr('role_id', $rid);
            $DB->query($SQL->get(dsn()), 'exec');

            //-----------
            // blog list
            $SQL    = SQL::newDelete('role_blog');
            $SQL->addWhereOpr('role_id', $rid);
            $DB->query($SQL->get(dsn()), 'exec');

            foreach ($Role->getArray('blog_list') as $bid ) {
                $SQL    = SQL::newInsert('role_blog');
                $SQL->addInsert('role_id', $rid);
                $SQL->addInsert('blog_id', $bid);
                $DB->query($SQL->get(dsn()), 'exec');
            }

            $this->Post->set('edit', 'update');
        }
        return $this->Post;
    }
}

