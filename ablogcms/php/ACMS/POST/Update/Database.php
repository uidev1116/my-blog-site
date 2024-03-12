<?php

use Acms\Services\Update\Engine;

class ACMS_POST_Update_Database extends ACMS_POST
{
    public function post()
    {
        if (!sessionWithAdministration()) {
            die();
        }
        if (RBID !== BID) {
            die();
        }
        if (SBID !== BID) {
            die();
        }

        $logger = App::make('update.logger');

        $updateService = new Engine($logger);
        $DB = DB::singleton(dsn());
        $DB->setThrowException(true);

        try {
            $updateService->validate(true);
            $updateService->dbUpdate();

            $this->addMessage(gettext('データベースのアップデートに成功しました。'));
            AcmsLogger::info('データベースのアップデートしました');
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            AcmsLogger::warning('データベースのアップデートに失敗しました。' . $e->getMessage(), Common::exceptionArray($e));
        }
        $DB->setThrowException(false);

        return $this->Post;
    }
}
