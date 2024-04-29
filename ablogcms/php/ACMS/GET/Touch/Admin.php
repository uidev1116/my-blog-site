<?php

class ACMS_GET_Touch_Admin extends ACMS_GET
{
    public function get()
    {
        return (ADMIN && !RVID) ? $this->tpl : '';
    }
}
