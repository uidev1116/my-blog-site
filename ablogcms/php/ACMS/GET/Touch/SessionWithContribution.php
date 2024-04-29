<?php

class ACMS_GET_Touch_SessionWithContribution extends ACMS_GET
{
    public $_scope = [
        'bid'   => 'global',
    ];

    function get()
    {
        return sessionWithContribution($this->bid) && !Preview::isPreviewMode() ? $this->tpl : false;
    }
}
