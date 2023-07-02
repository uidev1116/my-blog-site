<?php

class ACMS_GET_Touch_NotBlogParent extends ACMS_GET
{
    function get()
    {
        return !isBlogGlobal(BID) ? $this->tpl : false;
    }
}
