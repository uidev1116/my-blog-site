<?php

class ACMS_GET_Touch_ApprovalReject extends ACMS_GET
{
    function get()
    {
        return sessionWithApprovalReject(BID, CID) ? $this->tpl : false;
    }
}
