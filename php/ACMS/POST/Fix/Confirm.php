<?php

class ACMS_POST_Fix_Confirm extends ACMS_POST
{
    function post()
    {
        if ( !IS_LICENSED ) die();
        if ( !sessionWithAdministration() ) die();

        $Fix = $this->extract('fix', new ACMS_Validator());

        return $this->Post;
    }
}
