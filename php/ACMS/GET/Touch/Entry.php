<?php

class ACMS_GET_Touch_Entry extends ACMS_GET
{
    function get()
    {
        return (EID and !ADMIN) ? $this->tpl : '';
    }
}
