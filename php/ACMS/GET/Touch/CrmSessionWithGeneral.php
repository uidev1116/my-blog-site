<?php

class ACMS_GET_Touch_CrmSessionWithGeneral extends ACMS_GET
{
    function get()
    {
        return crmSessionWithGeneral() ? $this->tpl : false;
    }
}
