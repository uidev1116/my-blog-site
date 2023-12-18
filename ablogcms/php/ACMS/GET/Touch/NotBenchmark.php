<?php

class ACMS_GET_Touch_NotBenchmark extends ACMS_GET
{
    function get()
    {
        return !isBenchMarkMode() ? $this->tpl : false;
    }
}
