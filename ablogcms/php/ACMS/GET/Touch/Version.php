<?php

class ACMS_GET_Touch_Version extends ACMS_GET
{
    function get()
    {
        return enableRevision() ? $this->tpl : false;
    }
}
