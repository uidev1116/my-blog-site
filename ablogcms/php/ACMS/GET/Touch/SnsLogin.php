<?php

class ACMS_GET_Touch_SnsLogin extends ACMS_GET
{
    function get()
    {
        return ( 'on' == config('snslogin') ) ? $this->tpl : false;
    }
}
