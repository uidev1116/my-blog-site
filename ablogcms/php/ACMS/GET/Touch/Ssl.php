<?php

class ACMS_GET_Touch_Ssl extends ACMS_GET
{
    public function get()
    {
        return HTTPS ? $this->tpl : '';
    }
}
