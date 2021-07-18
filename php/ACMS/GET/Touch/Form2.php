<?php

class ACMS_GET_Touch_Form2 extends ACMS_GET
{
    function get()
    {
        return ('on' == config('form_edit_action_direct')) ? $this->tpl : false;
    }
}
