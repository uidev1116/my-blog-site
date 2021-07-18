<?php

class ACMS_GET_Touch_Role_BackupImport extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('backup_import', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
