<?php

class ACMS_GET_Admin_Backup_Index extends ACMS_GET_Admin
{
    public function get()
    {
        if ('backup_index' <> ADMIN) {
            return '';
        }

        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $logger = App::make('db.logger');
        $archivesLogger = App::make('archives.logger');
        $rootVars = [];

        /**
         * DBエクスポート中チェック
         */
        if (Storage::exists($logger->getDestinationPath())) {
            $rootVars['processing'] = 1;
        } else {
            $rootVars['processing'] = 0;
        }

        /**
         * アーカイブ、エクスポート中チェック
         */
        if (Storage::exists($archivesLogger->getDestinationPath())) {
            $rootVars['archivesProcessing'] = 1;
        } else {
            $rootVars['archivesProcessing'] = 0;
        }

        $tpl->add(null, $rootVars);
        return $tpl->get();
    }
}
