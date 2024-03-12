<?php

class ACMS_GET_Touch_EditInplace extends ACMS_GET
{
    function get()
    {
        if (
            1
            && !RVID
            && SUID
            && 'on' === config('entry_edit_inplace_enable')
            && 'on' === config('entry_edit_inplace')
            && (!enableApproval() || sessionWithApprovalAdministrator())
        ) {
            return $this->tpl;
        }
        return '';
    }
}
