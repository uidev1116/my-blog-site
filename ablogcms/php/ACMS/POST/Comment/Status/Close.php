<?php

class ACMS_POST_Comment_Status_Close extends ACMS_POST_Comment_Status
{
    function post()
    {
        return $this->_post('close');
    }
}
