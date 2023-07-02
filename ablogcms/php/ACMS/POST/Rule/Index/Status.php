<?php

class ACMS_POST_Rule_Index_Status extends ACMS_POST
{
    function post()
    {
        if ( !sessionWithAdministration() ) die();
        if ( !(($status = $this->Post->get('status')) and in_array($status, array('open', 'close'))) ) die();
        if ( !empty($_POST['checks']) and is_array($_POST['checks']) ) {
            $DB = DB::singleton(dsn());
            foreach ( $_POST['checks'] as $rid ) {
                if ( !$rid = idval($rid) ) continue;
                $SQL    = SQL::newUpdate('rule');
                $SQL->addUpdate('rule_status', $status);
                $SQL->addWhereOpr('rule_id', $rid);
                $SQL->addWhereOpr('rule_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');

                ACMS_RAM::rule($rid, null);
            }
        }

        return $this->Post;
    }
}
