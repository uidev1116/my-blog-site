<?php

use Acms\Services\Facades\Preview;

class ACMS_GET_Touch_SessionWithAdministration extends ACMS_GET
{
    public $_scope = [
        'bid'   => 'global',
    ];

    function get()
    {
        return sessionWithAdministration($this->bid) && !Preview::isPreviewMode() ? $this->tpl : false;
    }
}
