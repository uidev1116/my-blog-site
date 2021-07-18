<?php

class ACMS_GET_Touch_Debug extends ACMS_GET
{
    function get()
    {
        return DEBUG_MODE ? $this->tpl : false;
    }
}
