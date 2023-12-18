<?php

class ACMS_GET_Touch_EditionIsStandard extends ACMS_GET
{
    function get()
    {
        return editionIsStandard() ? $this->tpl : false;
    }
}
