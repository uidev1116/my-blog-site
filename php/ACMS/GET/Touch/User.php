<?php

class ACMS_GET_Touch_User extends ACMS_GET
{
    function get()
    {
        return UID ? $this->tpl : false;
    }
}
