<?php

class ACMS_GET_Touch_Admin extends ACMS_GET
{
    function get()
    {
        return (ADMIN && !RVID) ? $this->tpl : false;
    }
}
