<?php

class ACMS_GET_Touch_Role_NotEntryEdit extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('entry_edit', BID, EID) ? false : $this->tpl;
    }
}
