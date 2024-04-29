<?php

class ACMS_GET_Touch_HookEnable extends ACMS_GET
{
    public function get()
    {
        return HOOK_ENABLE ? $this->tpl : '';
    }
}
