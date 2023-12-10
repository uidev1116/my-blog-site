<?php

class ACMS_GET_Touch_Benchmark extends ACMS_GET
{
    function get()
    {
        return isBenchMarkMode() ? $this->tpl : false;
    }
}
