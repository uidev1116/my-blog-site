<?php

class ACMS_GET_Touch_ApprovalEditVersion extends ACMS_GET
{
    function get()
    {
        if (!enableApproval(BID, CID)) {
            return $this->tpl;
        }
        if (sessionWithApprovalAdministrator(BID, CID)) {
            return $this->tpl;
        }
        if (RVID && RVID > 1) {
            return $this->tpl;
        }
        return '';
    }
}
