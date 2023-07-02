<?php

class ACMS_GET_Touch_CrmSessionWithViewer extends ACMS_GET
{
    function get()
    {
        return crmSessionWithViewer() ? $this->tpl : false;
    }
}
