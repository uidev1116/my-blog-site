<?php

class ACMS_GET_Touch_User extends ACMS_GET
{
    public function get()
    {
        return UID ? $this->tpl : '';
    }
}
