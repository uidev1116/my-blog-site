<?php

class ACMS_GET_Module_Field extends ACMS_GET
{
    function get()
    {
        if (!$this->mid) {
            return '';
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $vars = $this->buildField(loadModuleField($this->mid), $Tpl);
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
