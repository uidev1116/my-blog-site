<?php

class ACMS_GET_Touch_NotTag extends ACMS_GET
{
    function get()
    {
        return !TAG ? $this->tpl : '';
    }
}
