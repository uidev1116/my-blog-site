<?php

class ACMS_GET_Response extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add('response:isValid#'.($this->Post->isValidAll() ? 'true' : 'false'));
        $Tpl->add(null, $this->buildField($this->Post, $Tpl));
        return $Tpl->get();
    }
}
