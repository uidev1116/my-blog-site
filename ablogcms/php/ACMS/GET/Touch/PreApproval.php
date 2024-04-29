<?php

class ACMS_GET_Touch_PreApproval extends ACMS_GET
{
    public function get()
    {
        if (!EID) {
            return '';
        }

        $entry = ACMS_RAM::entry(EID);

        return ($entry['entry_approval'] == 'pre_approval') ? $this->tpl : null;
    }
}
