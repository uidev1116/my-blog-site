<?php

class ACMS_GET_Touch_SessionWithEnterpriseAdministration extends ACMS_GET
{
    function get()
    {
        return sessionWithEnterpriseAdministration() ? $this->tpl : false;
    }
}
