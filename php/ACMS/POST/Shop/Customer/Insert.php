<?php

class ACMS_POST_Shop_Customer_Insert extends ACMS_POST_Shop
{   
    function post()
    {
        if ( !sessionWithAdministration() ) return die();

        return $this->Post;
    }
}