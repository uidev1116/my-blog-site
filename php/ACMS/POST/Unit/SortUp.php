<?php

class ACMS_POST_Unit_SortUp extends ACMS_POST_Unit
{
    function post()
    {
        $utid   = UTID;
        $eid    = EID;
        $entry  = ACMS_RAM::entry($eid);
        if (!roleEntryUpdateAuthorization(BID, $entry)) die();

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('column');
        $SQL->addSelect('column_sort');
        $SQL->addWhereOpr('column_id', $utid);
        $SQL->addWhereOpr('column_entry_id', $eid);
        $sort   = $DB->query($SQL->get(dsn()), 'one');

        if ( $sort == 1 ) return $this->Post;

        $above  = $sort - 1;

        // previous unit down
        $SQL    = SQL::newUpdate('column');
        $SQL->addUpdate('column_sort', $sort);
        $SQL->addWhereOpr('column_sort', $above);
        $SQL->addWhereOpr('column_entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        // current unit up
        $SQL    = SQL::newUpdate('column');
        $SQL->addUpdate('column_sort', $above);
        $SQL->addWhereOpr('column_id', $utid);
        $SQL->addWhereOpr('column_entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        $this->fixEntry($eid);

        return $this->Post;
    }
}
