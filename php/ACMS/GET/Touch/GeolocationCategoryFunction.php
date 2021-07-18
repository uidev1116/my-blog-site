<?php

class ACMS_GET_Touch_GeolocationCategoryFunction extends ACMS_GET
{
    function get()
    {
        return (config('geolocation_category_function') === 'on') ? $this->tpl : false;
    }
}
