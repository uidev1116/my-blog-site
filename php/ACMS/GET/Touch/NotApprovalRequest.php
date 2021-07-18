<?php

class ACMS_GET_Touch_NotApprovalRequest extends ACMS_GET
{
    function get()
    {
        return sessionWithApprovalRequest(BID, CID) ? false : $this->tpl;
    }
}
