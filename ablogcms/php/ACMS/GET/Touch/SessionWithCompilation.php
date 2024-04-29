<?php

class ACMS_GET_Touch_SessionWithCompilation extends ACMS_GET
{
    public $_scope = [
        'bid'   => 'global',
    ];

    function get()
    {
        return sessionWithCompilation($this->bid) && !Preview::isPreviewMode() ? $this->tpl : false;
    }
}
