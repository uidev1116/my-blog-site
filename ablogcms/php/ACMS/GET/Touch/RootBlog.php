<?php

class ACMS_GET_Touch_RootBlog extends ACMS_GET
{
    public function get()
    {
        return RBID === BID ? $this->tpl : '';
    }
}
