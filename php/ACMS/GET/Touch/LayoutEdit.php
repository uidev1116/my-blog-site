<?php

class ACMS_GET_Touch_LayoutEdit extends ACMS_GET
{
    function get()
    {
        return ( 1
            and sessionWithAdministration()
            and LAYOUT_EDIT
        ) ? $this->tpl : false;
    }
}
