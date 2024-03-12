<?php

class ACMS_GET_SystemError extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        if (!$this->Post->isChildExists('systemErrors')) {
            return '';
        }

        $errors = $this->Post->getChild('systemErrors')->getArray('error');
        foreach ($errors as $error) {
            $Tpl->add($error);
        }
        $Tpl->add(null);

        return $Tpl->get();
    }
}
