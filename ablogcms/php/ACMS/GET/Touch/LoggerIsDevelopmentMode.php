<?php

class ACMS_GET_Touch_LoggerIsDevelopmentMode extends ACMS_GET
{
    function get()
    {
        return !isProductionModeForLogger() ? $this->tpl : false;
    }
}
