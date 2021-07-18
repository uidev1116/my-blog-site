<?php

class ACMS_GET_Touch_SslEnable extends ACMS_GET
{
    function get()
    {
        return SSL_ENABLE ? $this->tpl : false;
    }
}
