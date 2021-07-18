<?php

class ACMS_GET_Touch_RelatedEntryFunction extends ACMS_GET
{
    function get()
    {
        return (config('related_entry_function') === 'on') ? $this->tpl : false;
    }
}
