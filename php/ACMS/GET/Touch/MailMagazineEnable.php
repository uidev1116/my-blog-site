<?php

class ACMS_GET_Touch_MailMagazineEnable extends ACMS_GET
{
    function get()
    {
        return ('on' == config('mailmagazine')) ? $this->tpl : false;
    }
}