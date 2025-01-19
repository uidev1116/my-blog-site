<?php

class ACMS_GET_Touch_DebugSetting extends ACMS_GET
{
    function get()
    {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            return $this->tpl;
        }
        if (defined('BENCHMARK_MODE') && BENCHMARK_MODE) {
            return $this->tpl;
        }
        return '';
    }
}
