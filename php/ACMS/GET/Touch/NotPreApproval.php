<?php

class ACMS_GET_Touch_NotPreApproval extends ACMS_GET
{
    function get()
    {
        if ( !EID ) return false;

        $entry = ACMS_RAM::entry(EID);

        return ($entry['entry_approval'] == 'pre_approval') ? null : $this->tpl;
    }
}