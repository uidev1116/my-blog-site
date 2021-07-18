<?php

class ACMS_GET_Touch_Role_NotMediaUpload extends ACMS_GET
{
    function get()
    {
        return roleAuthorization('media_upload', BID) ? false : $this->tpl;
    }
}
