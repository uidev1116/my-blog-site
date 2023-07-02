<?php

class ACMS_POST_Entry_Update_Sort extends ACMS_POST_Entry_Update
{
    function post()
    {
        $this->Post->setMethod('utid', 'required');
        $this->Post->setMethod('entry', 'operable', $this->isOperable());
        $this->Post->validate(new ACMS_Validator());
        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newUpdate('column');
            $SQL->addWhereOpr('column_blog_id', BID);

            $utidList = $this->Post->getArray('utid');

            foreach ( $utidList as $sort => $utid ) {
                $Q = clone $SQL;

                $Q->addUpdate('column_sort', $sort+1);
                $Q->addWhereOpr('column_id', $utid);
                $DB->query($Q->get(dsn()), 'exec');
            }

            $SQL = SQL::newUpdate('entry');
            $SQL->addUpdate('entry_current_rev_id', 0);
            $SQL->addUpdate('entry_last_update_user_id', SUID);
            $SQL->addWhereOpr('entry_id', EID);
            $SQL->addWhereOpr('entry_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::entry(EID, null);

            httpStatusCode('200 OK');
        } else {
            httpStatusCode('403 Forbidden');
        }
        header(PROTOCOL.' '.httpStatusCode());
        die(httpStatusCode());
    }
}
