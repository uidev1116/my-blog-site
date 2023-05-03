<?php

class ACMS_POST_Entry_Direct extends ACMS_POST_Unit
{
    function post()
    {
        $bid = $this->Post->get('bid');
        $eid = $this->Post->get('eid');
        $entry = ACMS_RAM::entry($eid);
        if (!roleEntryUpdateAuthorization(BID, $entry)) die();

        $sort = $this->getUnitSortEnd();
        $_POST['sort'][0] = $sort;

        $Column = Entry::extractColumn();
        $Res = Entry::saveColumn($Column, $eid, $bid, true);
        if ( $utid = $this->getInsertedUtid($sort) ) {
            $this->Post->set('utid', $utid);
        } else {
            die();
        }

        $primaryImageId_p = $this->Post->get('primary_image');
        $primaryImageId = empty($Res) ? null : (!UTID ? reset($Res) : (!empty($Res[UTID]) ? $Res[UTID] : reset($Res)));

        if ( intval($primaryImageId) > 0 && intval($primaryImageId_p) === intval($primaryImageId) ) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newUpdate('entry');
            $SQL->addUpdate('entry_primary_image', $primaryImageId);
            $SQL->addWhereOpr('entry_id', EID);
            $SQL->addWhereOpr('entry_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::entry(EID, null);

        }

        Common::saveFulltext('eid', $eid, Common::loadEntryFulltext($eid));
        $this->fixEntry($eid);

        return $this->Post;
    }

    protected function getUnitSortEnd()
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('column');
        $SQL->addSelect('*', null, null, 'count');
        $SQL->addWhereOpr('column_entry_id', EID);
        $offset = intval($DB->query($SQL->get(dsn()), 'one'));

        return $offset + 1;
    }

    protected function getInsertedUtid($sort = 0)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('column');
        $SQL->addSelect('column_id');
        $SQL->addWhereOpr('column_entry_id', EID);
        $SQL->addWhereOpr('column_sort', $sort);
        $SQL->setOrder('column_id', 'DESC');
        return intval($DB->query($SQL->get(dsn()), 'one'));
    }
}
