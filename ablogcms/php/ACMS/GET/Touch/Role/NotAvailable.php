<?php

class ACMS_GET_Touch_Role_NotAvailable extends ACMS_GET
{
    function get()
    {
        return roleAvailableUser() ? false : $this->tpl;
    }
}
