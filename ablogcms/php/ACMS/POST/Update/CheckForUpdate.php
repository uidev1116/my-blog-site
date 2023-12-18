<?php

use Acms\Services\Update\System\CheckForUpdate;

class ACMS_POST_Update_CheckForUpdate extends ACMS_POST
{
    public function post()
    {
        if (!sessionWithAdministration()) die();
        if (RBID !== BID) die();
        if (SBID !== BID) die();

        $check = App::make('update.check');
        $DB = DB::singleton(dsn());
        $DB->setThrowException(true);
        try {
            if ( !$check->check(phpversion(), CheckForUpdate::PATCH_VERSION) ) {
                return $this->Post;
            }
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            AcmsLogger::notice($e->getMessage(), Common::exceptionArray($e));
        }
        $DB->setThrowException(false);

        return $this->Post;
    }
}
