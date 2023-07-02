<?php

class ACMS_POST_StaticExport_Terminate extends ACMS_POST
{
    /**
     * Run
     */
    public function post()
    {
        if (!sessionWithAdministration()) die();

        $service = App::make('static-export.terminate-check');
        $service->terminate();

        sleep(3);

        $this->redirect(HTTP_REQUEST_URL);
    }
}