<?php

class ACMS_GET_Touch_NotEditionIsStandard extends ACMS_GET
{
    function get()
    {
        return editionIsStandard() ? false : $this->tpl;
    }
}
