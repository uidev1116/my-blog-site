<?php

class ACMS_GET_Touch_NotPreview extends ACMS_GET
{
    function get()
    {
        if (timemachineMode() || Preview::isPreviewMode()) {
            return '';
        }
        return $this->tpl;
    }
}
