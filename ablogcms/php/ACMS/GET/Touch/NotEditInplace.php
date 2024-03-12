<?php

class ACMS_GET_Touch_NotEditInplace extends ACMS_GET
{
    function get()
    {
        if (
            1
            && 'on' !== config('entry_edit_inplace_enable')
            && 'on' === config('entry_edit_inplace')
            && !(enableApproval() && !sessionWithApprovalAdministrator())
        ) {
            return $this->tpl;
        }
        return '';
    }
}
