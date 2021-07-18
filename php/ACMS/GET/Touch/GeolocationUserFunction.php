<?php

class ACMS_GET_Touch_GeolocationUserFunction extends ACMS_GET
{
    function get()
    {
        return (config('geolocation_user_function') === 'on') ? $this->tpl : false;
    }
}
