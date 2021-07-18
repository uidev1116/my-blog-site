<?php

class ACMS_GET_Touch_Role_ModuleEdit extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('module_edit', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
