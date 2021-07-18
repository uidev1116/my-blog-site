<?php

class ACMS_GET_Touch_Role_EntryDelete extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('entry_delete', BID, EID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
