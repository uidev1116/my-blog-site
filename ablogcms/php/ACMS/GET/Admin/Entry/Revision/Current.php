<?php

class ACMS_GET_Admin_Entry_Revision_Current extends ACMS_GET_Admin_Entry
{
    function get()
    {
        if ( !sessionWithContribution(BID, false) ) return 'Bad Access.';
        if ( !defined('EID') ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('entry');
        $SQL->addSelect('entry_current_rev_id');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $currentRvid = $DB->query($SQL->get(dsn()), 'one');
        $currentRvid = intval($currentRvid);
        if ( $currentRvid > 0 ) {
            $vars['currentVersion'] = $currentRvid;
        } else {
            $Tpl->add('notExistCurrentVersion');
        }
       
        $SQL    = SQL::newSelect('entry_rev');
        $SQL->addSelect('entry_id', 'revision_amount', null, 'COUNT');
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $count  = $DB->query($SQL->get(dsn()), 'one');
        if ( $count > 0 ) {
            $vars['amount'] = $count;
        } else {
            $Tpl->add('notFound');
        }

        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
