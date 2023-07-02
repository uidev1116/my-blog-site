<?php

class ACMS_POST_Ios_EntryUpdate extends ACMS_POST_Entry_Update
{
    function getEntryUpdateTime($eid)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('entry');
        $SQL->addSelect('entry_updated_datetime');
        $SQL->addWhereOpr('entry_id', $eid);
        $q = $SQL->get(dsn());
        $update_time = $DB->query($q, 'one');
        
        return $update_time;
    }

    /**
     * @see ACMS_POST_Entry_Update::update()
     */
    function post()
    {
        $fieldFlag  = $this->Get->get('fd');

        $beforeTime = $this->getEntryUpdateTime(EID);
        $updatedResponse = $this->update(true);
        $afterTime = $this->getEntryUpdateTime(EID);
        
        //-------
        // cache
        if ( $this->isCacheDelete ) {
            ACMS_POST_Cache::clearPageCache(BID);
        }

        if ( strtotime($beforeTime) < strtotime($afterTime) ) {
            exit(EID.':s:'.strval($updatedResponse['ecd']));
        } else {
            exit('FALSE:s:'.strval($updatedResponse['ecd']));
        }
        
    }
}