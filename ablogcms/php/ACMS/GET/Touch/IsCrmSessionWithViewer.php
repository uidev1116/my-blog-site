<?php

class ACMS_GET_Touch_IsCrmSessionWithViewer extends ACMS_GET
{
    function get()
    {
        return isCrmAuthViewer() ? $this->tpl : false;
    }
}
