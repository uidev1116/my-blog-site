<?php

class ACMS_GET_Touch_Role_Available extends ACMS_GET
{
    function get()
    {
        return roleAvailableUser() ? $this->tpl : false;
    }
}
