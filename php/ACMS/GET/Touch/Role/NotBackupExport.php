<?php

class ACMS_GET_Touch_Role_NotBackupExport extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('backup_export', BID) ? false : $this->tpl;
    }
}
