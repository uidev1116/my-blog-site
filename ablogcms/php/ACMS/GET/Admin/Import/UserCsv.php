<?php

class ACMS_GET_Admin_Import_UserCsv extends ACMS_GET_Admin
{
    public function get()
    {
        if ('import_user' !== ADMIN) {
            return '';
        }

        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $logger = App::make('common.logger');
        $logger->setDestinationPath(CACHE_DIR . 'user-csv-import-logger.json');
        $rootVars = [];

        /**
         * CSVインポート中チェック
         */
        if (Storage::exists($logger->getDestinationPath())) {
            $rootVars['processing'] = 1;
        } else {
            $rootVars['processing'] = 0;
        }
        $tpl->add(null, $rootVars);

        return $tpl->get();
    }
}
