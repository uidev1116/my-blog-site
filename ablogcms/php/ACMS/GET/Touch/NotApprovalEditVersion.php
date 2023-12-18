<?php

class ACMS_GET_Touch_NotApprovalEditVersion extends ACMS_GET
{
    function get()
    {
        if (enableApproval(BID, CID) && !sessionWithApprovalAdministrator(BID, CID)) {
            if (!RVID) {
                return $this->tpl;
            }
            if (RVID === 1) {
                return $this->tpl;
            }
        }
        return '';
    }
}
