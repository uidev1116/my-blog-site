<?php

class ACMS_GET_Touch_Role_MediaUpload extends ACMS_GET
{
    function get()
    {
        return ( roleAuthorization('media_upload', BID) || !roleAvailableUser() ) ? $this->tpl : false;
    }
}
