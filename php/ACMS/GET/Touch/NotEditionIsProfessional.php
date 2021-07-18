<?php

class ACMS_GET_Touch_NotEditionIsProfessional extends ACMS_GET
{
    function get()
    {
        return editionIsProfessional() ? false : $this->tpl;
    }
}
