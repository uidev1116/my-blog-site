<?php

class ACMS_GET_Touch_EntryExists extends ACMS_GET
{
    function get()
    {
        if ( 1
            && !!EID
            && ACMS_RAM::entryStatus(EID)
            && ACMS_RAM::entryStatus(EID) !== 'trash'
        ) {
            return $this->tpl;
        }
        return false;
    }
}
