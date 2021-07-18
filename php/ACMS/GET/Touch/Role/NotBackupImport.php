<?php

class ACMS_GET_Touch_Role_NotBackupImport extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('backup_import', BID) ? false : $this->tpl;
    }
}
