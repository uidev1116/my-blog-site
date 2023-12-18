<?php

class ACMS_GET_Touch_Role_BackupExport extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('backup_export', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
