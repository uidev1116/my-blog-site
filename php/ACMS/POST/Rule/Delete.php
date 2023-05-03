<?php

class ACMS_POST_Rule_Delete extends ACMS_POST_Rule
{
    function post()
    {
        if ( roleAvailableUser() ) {
            $this->Post->setMethod('rule', 'operative', 
                ($rid = idval($this->Get->get('rid'))) and roleAuthorization('rule_edit', BID)
            );
        } else {
            $this->Post->setMethod('rule', 'operative', 
                ($rid = idval($this->Get->get('rid'))) and sessionWithAdministration()
            );
        }

        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());

            //--------
            // cofnig
            $SQL    = SQL::newDelete('config');
            $SQL->addWhereOpr('config_rule_id', $rid);
            $SQL->addWhereOpr('config_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            Config::forgetCache(BID, $rid, null);

            //------
            // rule
            $SQL    = SQL::newDelete('rule');
            $SQL->addWhereOpr('rule_id', $rid);
            $SQL->addWhereOpr('rule_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            ACMS_RAM::rule($rid, null);

            $this->trim();

            $this->Post->set('edit', 'delete');
        }

        return $this->Post;
    }
}
