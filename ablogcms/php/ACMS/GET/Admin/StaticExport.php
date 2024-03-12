<?php

use Acms\Services\Facades\Storage;

class ACMS_GET_Admin_StaticExport extends ACMS_GET_Admin
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $rootVars = array();
        $logger = App::make('static-export.logger');

        /**
         * 書き出し中チェック
         */
        if (Storage::exists($logger->getDestinationPath())) {
            $rootVars['processing'] = 1;
        } else {
            $rootVars['processing'] = 0;
            $rootVars['last-time-date'] = config('static-export-last-time-date', '1000-01-01');
            $rootVars['last-time-time'] = config('static-export-last-time-time', '00:00:00');
        }
        $Tpl->add(null, $rootVars);

        return $Tpl->get();
    }
}
