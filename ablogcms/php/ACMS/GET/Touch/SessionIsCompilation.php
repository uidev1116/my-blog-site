<?php

class ACMS_GET_Touch_SessionIsCompilation extends ACMS_GET
{
    public $_scope = array(
        'bid'   => 'global',
    );

    function get()
    {
        return (isSessionEditor() && sessionWithCompilation($this->bid) && !Preview::isPreviewMode()) ? $this->tpl : false;
    }
}
