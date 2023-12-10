<?php

class ACMS_GET_Touch_Debug extends ACMS_GET
{
    function get()
    {
        return isDebugMode() ? $this->tpl : false;
    }
}
