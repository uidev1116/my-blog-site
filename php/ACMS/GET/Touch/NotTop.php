<?php

class ACMS_GET_Touch_NotTop extends ACMS_GET
{
    function get()
    {
        return !('top' == VIEW) ? $this->tpl : '';
    }
}
