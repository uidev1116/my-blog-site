<?php

class ACMS_POST_Ios_EntryUpdateField extends ACMS_POST_Entry_Update
{
    function post()
    {
        if ( !(1
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
        )) ) die();

        $Field  = $this->extract('field', new ACMS_Validator());
        Common::saveField('eid', EID, $Field);

        //----------
        // fulltext
        $Return = new Field();
        $Return->set('backend', true);

        return $Return;
    }
}
