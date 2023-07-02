<?php

class ACMS_GET_Touch_Role_AdminEtc extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('admin_etc', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
