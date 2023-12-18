<?php

class ACMS_GET_Touch_NotLayoutEdit extends ACMS_GET
{
    function get()
    {
        return ( 0
            || !sessionWithAdministration()
            || LAYOUT_EDIT
        ) ? false : $this->tpl;
    }
}
