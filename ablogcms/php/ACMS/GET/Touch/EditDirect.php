<?php

class ACMS_GET_Touch_EditDirect extends ACMS_GET
{
    function get()
    {
        return ('on' == config('entry_edit_action_direct') && 'on' == config('entry_edit_inplace_enable') && ( !enableApproval() || sessionWithApprovalAdministrator() ) ) ? $this->tpl : false;
    }
}
