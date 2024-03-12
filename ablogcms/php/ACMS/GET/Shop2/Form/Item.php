<?php

class ACMS_GET_Shop2_Form_Item extends ACMS_GET_Shop2
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(null, $this->buildField($this->Post, $Tpl));

        return $Tpl->get();
    }
}
