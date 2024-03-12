<?php

use Acms\Services\Facades\Common;

class ACMS_GET_Js extends ACMS_GET
{
    function get()
    {
        App::setIsAcmsJsLoaded(true);
        $jsModules = Common::getJsModules();
        $query = join('&', $jsModules);

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        if (!empty($query)) {
            $Tpl->add(null, array(
            'arguments' => '?' . $query,
            ));
        }
        return $Tpl->get();
    }
}
