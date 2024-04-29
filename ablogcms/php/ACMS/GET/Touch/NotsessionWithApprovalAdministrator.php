<?php

class ACMS_GET_Touch_NotsessionWithApprovalAdministrator extends ACMS_GET
{
    public function get()
    {
        if (enableApproval(BID, CID) && !sessionWithApprovalAdministrator(BID, CID)) {
            return $this->tpl;
        } else {
            return '';
        }
    }
}
