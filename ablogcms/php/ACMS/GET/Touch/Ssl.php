<?php

class ACMS_GET_Touch_Ssl extends ACMS_GET
{
    function get()
    {
        return HTTPS ? $this->tpl : false;
    }
}
