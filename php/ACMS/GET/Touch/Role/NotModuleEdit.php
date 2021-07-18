<?php

class ACMS_GET_Touch_Role_NotModuleEdit extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('module_edit', BID) ? false : $this->tpl;
    }
}
