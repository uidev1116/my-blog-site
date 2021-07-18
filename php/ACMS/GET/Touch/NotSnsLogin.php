<?php

class ACMS_GET_Touch_NotSnsLogin extends ACMS_GET
{
    function get()
    {
        return ( 'on' == config('snslogin') ) ? false : $this->tpl;
    }
}
