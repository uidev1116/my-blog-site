<?php

class ACMS_POST_Comment_Status_Open extends ACMS_POST_Comment_Status
{
    function post()
    {
        return $this->_post('open');
    }
}
