<?php

class ACMS_GET_Touch_NotCategory extends ACMS_GET
{
    function get()
    {
        return !CID ? $this->tpl : false;
    }
}
