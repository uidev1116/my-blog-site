<?php

class ACMS_GET_Touch_SslEnable extends ACMS_GET
{
    public function get()
    {
        return SSL_ENABLE ? $this->tpl : '';
    }
}
