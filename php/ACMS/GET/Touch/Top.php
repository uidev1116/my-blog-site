<?php

class ACMS_GET_Touch_Top extends ACMS_GET
{
    function get()
    {
        return ('top' == VIEW) ? $this->tpl : '';
    }
}
