<?php

class ACMS_GET_Touch_NotHookEnable extends ACMS_GET
{
    function get()
    {
        return HOOK_ENABLE ? false : $this->tpl;
    }
}
