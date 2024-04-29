<?php

class ACMS_GET_Touch_SessionIsContribution extends ACMS_GET
{
    public $_scope = [
        'bid'   => 'global',
    ];

    function get()
    {
        return (isSessionContributor() && sessionWithContribution($this->bid) && !Preview::isPreviewMode()) ? $this->tpl : false;
    }
}
