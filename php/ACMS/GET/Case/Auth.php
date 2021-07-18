<?php

class ACMS_GET_Case_Auth extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        if ( !SUID ) return false;
        $Tpl->add(ACMS_RAM::userAuth(SUID));
        return $Tpl->get();
    }
}
