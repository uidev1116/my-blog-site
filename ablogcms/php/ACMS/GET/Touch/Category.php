<?php

class ACMS_GET_Touch_Category extends ACMS_GET
{
    public function get()
    {
        return CID ? $this->tpl : '';
    }
}
