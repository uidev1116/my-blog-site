<?php

class ACMS_GET_Touch_NotApproval extends ACMS_GET
{
    function get()
    {
        return enableApproval(BID, CID) ? false : $this->tpl;
    }
}
