<?php

class ACMS_GET_Touch_RootBlog extends ACMS_GET
{
    function get()
    {
        return RBID === BID ? $this->tpl : false;
    }
}
