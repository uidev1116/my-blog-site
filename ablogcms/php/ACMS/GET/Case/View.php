<?php

class ACMS_GET_Case_View extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(VIEW);
        return $Tpl->get();
    }
}
