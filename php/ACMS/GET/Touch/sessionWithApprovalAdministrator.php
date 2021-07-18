<?php

class ACMS_GET_Touch_sessionWithApprovalAdministrator extends ACMS_GET
{
    function get()
    {
        if ( 0
            || !enableApproval(BID, CID)
            || ( enableApproval(BID) && sessionWithApprovalAdministrator(BID, CID) )
        ) {
            return $this->tpl;
        } else {
            return false;
        }
    }
}
