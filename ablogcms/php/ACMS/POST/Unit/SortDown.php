<?php

class ACMS_POST_Unit_SortDown extends ACMS_POST_Unit
{
    function post()
    {
        $utid   = UTID;
        $eid    = EID;
        $entry  = ACMS_RAM::entry($eid);
        if (!roleEntryUpdateAuthorization(BID, $entry)) {
            die();
        }

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('column');
        $SQL->addSelect('column_sort');
        $SQL->addWhereOpr('column_id', $utid);
        $SQL->addWhereOpr('column_entry_id', $eid);
        $sort   = $DB->query($SQL->get(dsn()), 'one');

        $SQL    = SQL::newSelect('column');
        $SQL->addSelect('column_sort', 'sort_max', null, 'MAX');
        $SQL->addWhereOpr('column_entry_id', $eid);
        $max    = $DB->query($SQL->get(dsn()), 'one');

        if ($sort == $max) {
            return $this->Post;
        }

        $below  = $sort + 1;

        // previous unit up
        $SQL    = SQL::newUpdate('column');
        $SQL->addUpdate('column_sort', $sort);
        $SQL->addWhereOpr('column_sort', $below);
        $SQL->addWhereOpr('column_entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        // current unit down
        $SQL    = SQL::newUpdate('column');
        $SQL->addUpdate('column_sort', $below);
        $SQL->addWhereOpr('column_id', $utid);
        $SQL->addWhereOpr('column_entry_id', $eid);
        $DB->query($SQL->get(dsn()), 'exec');

        $this->fixEntry($eid);

        AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーの「' . $sort . '」番目のユニットを下に移動しました');

        return $this->Post;
    }
}
