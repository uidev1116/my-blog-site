<?php

class ACMS_GET_Touch_BlogParent extends ACMS_GET
{
    function get()
    {
        return isBlogGlobal(BID) ? $this->tpl : false;
    }
}
