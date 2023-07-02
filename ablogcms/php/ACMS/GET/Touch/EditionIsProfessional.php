<?php

class ACMS_GET_Touch_EditionIsProfessional extends ACMS_GET
{
    function get()
    {
        return editionIsProfessional() ? $this->tpl : false;
    }
}
