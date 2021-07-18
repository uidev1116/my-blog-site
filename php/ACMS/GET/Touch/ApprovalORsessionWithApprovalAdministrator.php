<?php

class ACMS_GET_Touch_ApprovalORsessionWithApprovalAdministrator extends ACMS_GET
{
    function get()
    {
        return (!enableApproval(BID, CID) || sessionWithApprovalAdministrator(BID, CID)) ? false : $this->tpl;
    }
}

