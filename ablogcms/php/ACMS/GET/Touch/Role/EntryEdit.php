<?php

class ACMS_GET_Touch_Role_EntryEdit extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('entry_edit', BID, EID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
