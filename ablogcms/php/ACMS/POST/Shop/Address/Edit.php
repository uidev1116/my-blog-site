<?php

class ACMS_POST_Shop_Address_Edit extends ACMS_POST_Shop
{
    function post()
    {
        $this->Post->set('step', 'reapply');
        return $this->Post;
    }
}
