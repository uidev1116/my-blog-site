<?php

class ACMS_GET_Touch_SubCategoryFunction extends ACMS_GET
{
    function get()
    {
        return (config('sub_category_function') === 'on') ? $this->tpl : false;
    }
}
