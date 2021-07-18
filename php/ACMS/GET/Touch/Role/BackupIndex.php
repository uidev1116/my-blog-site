<?php

class ACMS_GET_Touch_Role_BackupIndex extends ACMS_GET
{
    function get()
    {
        if ( roleAvailableUser() ) {
            return ( roleAuthorization('backup_export', BID) || roleAuthorization('backup_import', BID) || !roleAvailableUser() ) ? $this->tpl : false;
        } else {
            return sessionWithAdministration(BID) ? $this->tpl : false;
        }
    }
}
