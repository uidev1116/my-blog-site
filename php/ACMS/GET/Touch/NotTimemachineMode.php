<?php

class ACMS_GET_Touch_NotTimemachineMode extends ACMS_GET
{
    function get()
    {
        return timemachineMode() ? false : $this->tpl;
    }
}