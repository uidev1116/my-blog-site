<?php

use Acms\Services\Facades\Preview;

class ACMS_GET_Admin_Preview_Share extends ACMS_GET
{
    function get()
    {
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        try {
            $url = Preview::getSharePreviewUrl();
            return $tpl->render(array(
                'url' => $url,
            ));
        } catch (\Exception $e) {
        }

        return $tpl->get();
    }
}
