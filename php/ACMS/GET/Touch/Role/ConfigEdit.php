<?php

class ACMS_GET_Touch_Role_ConfigEdit extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('config_edit', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
