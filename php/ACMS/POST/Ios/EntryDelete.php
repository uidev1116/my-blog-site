<?php

class ACMS_POST_Ios_EntryDelete extends ACMS_POST_Trash
{
    function checkDelete($eid){
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('entry');
        $SQL->addSelect('entry_title');
        $SQL->addWhereOpr('entry_status', 'trash', '<>');
        $SQL->addWhereOpr('entry_id', $eid);
        $q = $SQL->get(dsn());
        $entry_title = $DB->query($q, 'all');
        
        return empty($entry_title);
    }

    /**
     * @see ACMS_POST_Entry_Delete::delete()
     */
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('entry', 'operable', (1
            and !!($eid = intval($this->Post->get('eid', EID)))
            and !!($ebid = ACMS_RAM::entryBlog($eid))
            and ACMS_RAM::blogLeft(SBID) <= ACMS_RAM::blogLeft($ebid)
            and ACMS_RAM::blogRight(SBID) >= ACMS_RAM::blogRight($ebid)
            and ( 0
                or sessionWithCompilation()
                or ( 1
                    and sessionWithContribution()
                    and SUID == ACMS_RAM::entryUser($eid)
                )
            )
        ));
        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $this->trash($eid);
            $boolean = $this->checkDelete($eid);
            
            //-------
            // cache
            if ( $this->isCacheDelete ) {
                ACMS_POST_Cache::clearPageCache(BID);
            }
            
            die(strval($boolean));
        }
    }
}