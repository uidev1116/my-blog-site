<?php

use Acms\Services\Facades\Preview;

class ACMS_POST_Preview_Disable extends ACMS_POST
{
    function post()
    {
        Preview::endPreviewMode();
        die();
    }
}
