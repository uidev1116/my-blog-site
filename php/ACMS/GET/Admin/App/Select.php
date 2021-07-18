<?php

class ACMS_GET_Admin_App_Select extends ACMS_GET_Admin
{
    function get()
    {
        if ( !sessionWithAdministration() ) return '';

        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $list       = scandir(AAPP_LIB_DIR);
        $exsits     = false;
        foreach ( $list as $fd ) {

            if (Storage::isFile(AAPP_LIB_DIR.$fd)) {
                $appName    = str_replace('.php', '', $fd);
                $className  = 'AAPP_'.$appName;
                if ( !class_exists($className) ) {
                    continue;
                }
                $App  = new $className();
                if ( !$App instanceof ACMS_App ) {
                    continue;
                }
                if ( !$App->module ) {
                    continue;
                }
                $className = 'App_'.$appName;

                $DB     = DB();
                $SQL    = SQL::newSelect('app');
                $SQL->addWhereOpr('app_name', 'AAPP_'.$appName);

                // DBになければインストール前として扱う
                $status = 'init';

                if ( !!($all = $DB->query($SQL->get(dsn()), 'all')) ) {

                    $existsOnThisBlog = false;
                    foreach ( $all as $row ) {
                        if ( intval($row['app_blog_id']) === BID ) {
                            $existsOnThisBlog = $row;
                        }
                    }

                    if ( $existsOnThisBlog ) {
                        $status = $existsOnThisBlog['app_status'];
                    } else {
                        $status = 'off';
                    }

                    if ( version_compare($row['app_version'], $App->version) ) {
                        $status = 'update';
                    }
                }
                if ( !in_array($status, array('on', 'update')) ) {
                    continue;
                }

                $vars = array(
                    'name'      => $App->name,
                    'className' => $className,
                );
                $Tpl->add('app:loop', $vars);
                $exsits = true;
            }
        }
        if ( !$exsits ) return '';

        return $Tpl->get();
    }
}
