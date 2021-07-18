<?php

class ACMS_GET_Touch_NotKeyword extends ACMS_GET
{
    function get()
    {
        return (!KEYWORD and !ADMIN) ? $this->tpl : '';
    }
}
