<?php

class ACMS_GET_Touch_NotIndex extends ACMS_GET
{
    function get()
    {
        return ('top' == VIEW or 'entry' == VIEW) ? $this->tpl : '';
    }
}
