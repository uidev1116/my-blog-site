<?php

class ACMS_GET_Touch_NotEntry extends ACMS_GET
{
    function get()
    {
        return !(EID and !ADMIN) ? $this->tpl : '';
    }
}
