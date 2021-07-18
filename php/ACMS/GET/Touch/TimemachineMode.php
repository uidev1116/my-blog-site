<?php

class ACMS_GET_Touch_TimemachineMode extends ACMS_GET
{
    function get()
    {
        return timemachineMode() ? $this->tpl : false;
    }
}