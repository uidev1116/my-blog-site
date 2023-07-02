<?php

use Acms\Services\Facades\Preview;

class ACMS_POST_Timemachine_Disable extends ACMS_POST
{
    function post()
    {
        $session =& Field::singleton('session');
        $session->delete('timemachine_datetime');
        $session->delete('timemachine_rule_id');
        Preview::endPreviewMode();
        die('OK');
    }
}
