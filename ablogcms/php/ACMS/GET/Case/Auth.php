<?php

class ACMS_GET_Case_Auth extends ACMS_GET
{
    public function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        if (!SUID) {
            return '';
        }
        $Tpl->add(ACMS_RAM::userAuth(SUID));
        return $Tpl->get();
    }
}
