<?php

class ACMS_POST_Logout extends ACMS_POST
{
    var $isCacheDelete  = false;

    function post()
    {
        if (SUID) {
            logout();
        }

        $this->redirect(acmsLink(array(
            'bid' => BID,
        ), false), false);
    }
}
