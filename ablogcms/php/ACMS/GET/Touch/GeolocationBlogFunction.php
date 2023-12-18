<?php

class ACMS_GET_Touch_GeolocationBlogFunction extends ACMS_GET
{
    function get()
    {
        return (config('geolocation_blog_function') === 'on') ? $this->tpl : false;
    }
}
