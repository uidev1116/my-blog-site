<?php

class ACMS_GET_Touch_Unlogin extends ACMS_GET
{
    function get()
    {
        return !ACMS_SID ? $this->tpl : false;
    }
}
