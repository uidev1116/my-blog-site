<?php

class ACMS_GET_Touch_Index extends ACMS_GET
{
    function get()
    {
        return !('top' == VIEW or 'entry' == VIEW) ? $this->tpl : '';
    }
}
