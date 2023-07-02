<?php

class ACMS_GET_Touch_Role_NotAdminEtc extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('admin_etc', BID) ) ? false : $this->tpl;
    }
}
