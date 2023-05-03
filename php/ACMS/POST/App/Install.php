<?php

class ACMS_POST_App_Install extends ACMS_POST
{
    public function post()
    {
        $appClassName = $this->Post->get('class_name');
        if ( !sessionWithAdministration() || BID !== RBID ) die;

        /**
         * @var ACMS_App $App
         */
        $App = new $appClassName();

        if ( $App->checkRequirements() ) {
            try {
                $App->install();
                $App->activate();

                $DB = DB::singleton(dsn());
                $SQL = SQL::newInsert('app');
                $SQL->addInsert('app_name',    get_class($App));
                $SQL->addInsert('app_version', $App->version);
                $SQL->addInsert('app_status',  'on');
                $SQL->addInsert('app_install_datetime', date('Y-m-d H:i:s'));
                $SQL->addInsert('app_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');

                Cache::flush('template');
                Cache::flush('config');
                Cache::flush('field');
                Cache::flush('temp');

                $this->Post->set('installSucceed', true);
            } catch(Exception $e) {
                $this->Post->set('installFailed', true);
            }
        } else {
            $this->Post->set('requirementsNotEnough', true);
        }

        return $this->Post;
    }
}
