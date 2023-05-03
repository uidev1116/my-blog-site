<?php

class ACMS_POST_App_Uninstall extends ACMS_POST
{
    public function post()
    {
        $appClassName = $this->Post->get('class_name');
        if ( !sessionWithAdministration() || BID !== RBID  ) die;

        /**
         * @var ACMS_App $App
         */
        $App = new $appClassName();

        try {
            $App->uninstall();

            $DB = DB::singleton(dsn());
            $SQL = SQL::newDelete('app');
            $SQL->addWhereOpr('app_name', get_class($App));
            $DB->query($SQL->get(dsn()), 'exec');

            Cache::flush('template');
            Cache::flush('config');
            Cache::flush('field');
            Cache::flush('temp');

            $this->Post->set('uninstallSucceed', true);
        } catch(Exception $e) {
            $this->Post->set('uninstallFailed', true);
        }
        return $this->Post;
    }
}
