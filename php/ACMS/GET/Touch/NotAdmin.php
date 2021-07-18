<?php

class ACMS_GET_Touch_NotAdmin extends ACMS_GET
{
    function get()
    {
        return !ADMIN ? $this->tpl : false;
    }
}
