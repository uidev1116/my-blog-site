<?php

class ACMS_GET_Touch_Tag extends ACMS_GET
{
    public function get()
    {
        return !!TAG ? $this->tpl : '';
    }
}
