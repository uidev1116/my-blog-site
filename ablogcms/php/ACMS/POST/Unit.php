<?php

class ACMS_POST_Unit extends ACMS_POST
{
    function post()
    {
        return $this->Post;
    }

    function fixEntry($eid)
    {
        $DB     = DB::singleton(dsn());

        $SQL = SQL::newUpdate('entry');
        $SQL->addUpdate('entry_current_rev_id', 0);
        $SQL->addUpdate('entry_reserve_rev_id', 0);
        $SQL->addUpdate('entry_last_update_user_id', SUID);
        $SQL->addUpdate('entry_updated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $SQL->addWhereOpr('entry_id', $eid);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::entry($eid, null);
    }
}
