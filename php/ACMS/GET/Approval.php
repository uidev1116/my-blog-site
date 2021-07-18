<?php

class ACMS_GET_Approval extends ACMS_GET
{
    function get ()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(null, $this->buildField($this->Post, $Tpl));
        return $Tpl->get();
    }
}
