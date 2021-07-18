<?php

class ACMS_GET_Touch_GeolocationEntryFunction extends ACMS_GET
{
    function get()
    {
        return (config('geolocation_entry_function') === 'on') ? $this->tpl : false;
    }
}
