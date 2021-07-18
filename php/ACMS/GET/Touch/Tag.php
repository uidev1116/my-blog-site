<?php

class ACMS_GET_Touch_Tag extends ACMS_GET
{
    function get()
    {
        return !!TAG ? $this->tpl : false;
    }
}
