<?php

class ACMS_GET_Touch_NotDebug extends ACMS_GET
{
    function get()
    {
        return !DEBUG_MODE ? $this->tpl : false;
    }
}
