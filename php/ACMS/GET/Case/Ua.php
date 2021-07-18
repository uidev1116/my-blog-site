<?php

class ACMS_GET_Case_Ua extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(config('ua'));
        return $Tpl->get();
    }
}
