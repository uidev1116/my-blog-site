<?php

class ACMS_GET_Touch_NotApprovalORsessionWithApprovalAdministrator extends ACMS_GET
{
    function get()
    {
        return (!enableApproval(BID, CID) || sessionWithApprovalAdministrator(BID, CID)) ? $this->tpl : false;
    }
}

