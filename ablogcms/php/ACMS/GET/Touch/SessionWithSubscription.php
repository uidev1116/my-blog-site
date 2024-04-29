<?php

class ACMS_GET_Touch_SessionWithSubscription extends ACMS_GET
{
    public $_scope = [
        'bid'   => 'global',
    ];

    function get()
    {
        return sessionWithSubscription($this->bid) && !Preview::isPreviewMode() ? $this->tpl : false;
    }
}
