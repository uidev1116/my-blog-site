<?php

class ACMS_POST_Entry_Update_Title extends ACMS_POST_Entry_Update
{
    function post()
    {
        $this->Post->setMethod('title', 'required');
        $this->Post->setMethod('entry', 'operable', $this->isOperable());
        $this->Post->validate(new ACMS_Validator());
        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newUpdate('entry');
            $SQL->addUpdate('entry_title', $this->Post->get('title'));
            $SQL->addWhereOpr('entry_id', EID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::entry(EID, null);
            $this->clearCache(BID, EID);

            httpStatusCode('200 OK');
        } else {
            httpStatusCode('403 Forbidden');
        }
        header(PROTOCOL.' '.httpStatusCode());
        die(httpStatusCode());
    }
}
