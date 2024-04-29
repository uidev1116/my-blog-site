<?php

class ACMS_GET_Touch_NotPreApproval extends ACMS_GET
{
    public function get()
    {
        if (!EID) {
            return '';
        }

        $entry = ACMS_RAM::entry(EID);

        return ($entry['entry_approval'] == 'pre_approval') ? null : $this->tpl;
    }
}
