<?php

class ACMS_GET_Touch_Role_NotEntryDelete extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('entry_delete', BID, EID) ? false : $this->tpl;
    }
}
