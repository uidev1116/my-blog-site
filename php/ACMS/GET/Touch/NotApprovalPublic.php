<?php

class ACMS_GET_Touch_NotApprovalPublic extends ACMS_GET
{
    function get()
    {
        return sessionWithApprovalPublic(BID, CID) ? false : $this->tpl;
    }
}
