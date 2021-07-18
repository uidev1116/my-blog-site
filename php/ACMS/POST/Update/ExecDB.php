<?php

use Acms\Services\Update\Engine;

class ACMS_POST_Update_ExecDB extends ACMS_POST_Update_Exec
{
    /**
     * Run update.
     */
    protected function run()
    {
        if (!sessionWithAdministration()) die();
        if ('update' <> ADMIN) die();
        if (RBID !== BID) die();
        if (SBID !== BID) die();

        set_time_limit(0);

        $logger = App::make('update.logger');
        $logger->load();

        DB::setThrowException(true);

        try {
            // Database Update
            $dbUpdateService = new Engine($logger);
            $dbUpdateService->validate();
            $dbUpdateService->update();

            $logger->success();
        } catch ( \Exception $e ) {
            $message = $e->getMessage();
            if ( !empty($message) ) {
                $logger->error($e->getMessage());
            }
        }
        DB::setThrowException(false);

        sleep(3);
        $logger->terminate();
    }
}