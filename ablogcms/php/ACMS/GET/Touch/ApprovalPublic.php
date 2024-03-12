<?php

class ACMS_GET_Touch_ApprovalPublic extends ACMS_GET
{
    function get()
    {
        return sessionWithApprovalPublic(BID, CID) ? $this->tpl : false;
    }
}
