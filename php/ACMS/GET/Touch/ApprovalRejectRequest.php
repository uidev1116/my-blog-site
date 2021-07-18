<?php

class ACMS_GET_Touch_ApprovalRejectRequest extends ACMS_GET
{
    function get()
    {
        return sessionWithApprovalRejectRequest(BID, CID) ? $this->tpl : false;
    }
}
