<?php

class ACMS_GET_Filter_SetGlobalVars extends ACMS_GET_Filter
{
    function get()
    {
        return setGlobalVars($this->tpl);
    }
}
