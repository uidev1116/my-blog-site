<?php

class ACMS_POST_Trash extends ACMS_POST_Entry
{
    function trash($eid = EID)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newUpdate('entry');
        $SQL->addUpdate('entry_status', 'trash');
        $SQL->addUpdate('entry_updated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $SQL->addUpdate('entry_delete_uid', SUID);
        $SQL->addWhereOpr('entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        ACMS_RAM::entry($eid, null);

        //-----------------------
        // キャッシュクリア予約削除
        Entry::deleteCacheControl($eid);
    }

    function restore($eid = EID)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newUpdate('entry');
        $SQL->addUpdate('entry_status', 'close');
        $SQL->addUpdate('entry_updated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $SQL->addUpdate('entry_delete_uid', null);
        $SQL->addWhereOpr('entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::entry($eid, null);
    }

    function post()
    {
        return $this->Post;
    }
}
