<?php

class ACMS_GET_Touch_RevisionAdministrator extends ACMS_GET
{
    function get()
    {
        if ( 0
            || !enableApproval(BID, CID)
            || sessionWithApprovalAdministrator(BID, CID)
        ) {
            return $this->tpl;
        }
        return false;
    }
}
