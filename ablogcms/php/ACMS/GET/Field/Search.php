<?php

class ACMS_GET_Field_Search extends ACMS_GET
{
    public $_scope = [
        'field' => 'global',
    ];

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        if (empty($this->Field)) {
            $Tpl->add();
            return $Tpl->get();
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = $this->buildField($this->Field, $Tpl);
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
