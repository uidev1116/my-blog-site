<?php

class ACMS_GET_Touch_BlogGlobal extends ACMS_GET
{
    function get()
    {
        return isBlogGlobal(SBID) ? $this->tpl : false;
    }
}
