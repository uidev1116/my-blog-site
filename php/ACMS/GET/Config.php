<?php

class ACMS_GET_Config extends ACMS_GET
{
    function get ()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(null, $this->buildField(Field::singleton('config'), $Tpl));
        return $Tpl->get();
    }
}
