<?php

class ACMS_POST_App_Deactivate extends ACMS_POST
{
    public function post()
    {
        $appClassName = $this->Post->get('class_name');
        if ( !sessionWithAdministration() ) die;

        /**
         * @var ACMS_App $App
         */
        $App = new $appClassName();

        try {
            $App->deactivate();

            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('app');
            $SQL->addWhereOpr('app_name', get_class($App));
            $SQL->addWhereOpr('app_blog_id', BID);
            
            if ( $row = $DB->query($SQL->get(dsn()), 'one') ) {
                $SQL = SQL::newUpdate('app');
                $SQL->addUpdate('app_status',  'off');
                $SQL->addupdate('app_activate_datetime', date('Y-m-d H:i:s'));
                $SQL->addWhereOpr('app_name', get_class($App));
                $SQL->addWhereOpr('app_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
            } else {
                $SQL = SQL::newInsert('app');
                $SQL->addInsert('app_name',    get_class($App));
                $SQL->addInsert('app_version', $App->version);
                $SQL->addInsert('app_status',  'off');
                $SQL->addInsert('app_activate_datetime', date('Y-m-d H:i:s'));
                $SQL->addInsert('app_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
            }

            $this->Post->set('deactivateSucceed', true);
        } catch(Exception $e) {
            $this->Post->set('deactivateFailed', true);
        }

        return $this->Post;
    }
}