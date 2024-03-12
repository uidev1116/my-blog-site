<?php

class ACMS_POST_Update_InitLog extends ACMS_POST_Update_Base
{
    public function post()
    {
        $logger = App::make('update.logger');
        $logger->terminate();
        $this->removeLockFile();
    }
}
