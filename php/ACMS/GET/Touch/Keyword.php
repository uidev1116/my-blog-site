<?php

class ACMS_GET_Touch_Keyword extends ACMS_GET
{
    function get()
    {
        return (!!KEYWORD and !ADMIN) ? $this->tpl : '';
    }
}
