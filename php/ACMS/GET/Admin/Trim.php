<?php

class ACMS_GET_Admin_Trim extends ACMS_GET
{
    function get()
    {
        $Tpl = $this->tpl;

        $Tpl = strip_tags($Tpl);
        $Tpl = trim(mb_convert_kana($Tpl, "s"));
        $Tpl = str_replace(array("\r\n","\r","\n"), '', $Tpl);

        return $Tpl;
    }
}
