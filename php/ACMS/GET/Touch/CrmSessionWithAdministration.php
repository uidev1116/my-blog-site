<?php

class ACMS_GET_Touch_CrmSessionWithAdministration extends ACMS_GET
{
    function get()
    {
        return isCrmAuthAdministrator() ? $this->tpl : false;
    }
}
