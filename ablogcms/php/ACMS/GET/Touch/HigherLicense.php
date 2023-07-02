<?php

class ACMS_GET_Touch_HigherLicense extends ACMS_GET
{
    function get()
    {
        return editionWithProfessional() ? $this->tpl : false;
    }
}
