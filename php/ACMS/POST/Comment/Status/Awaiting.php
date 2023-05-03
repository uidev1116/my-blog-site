<?php

class ACMS_POST_Comment_Status_Awaiting extends ACMS_POST_Comment_Status
{
    function post()
    {
        return $this->_post('awaiting');
    }
}
