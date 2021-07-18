<?php

class ACMS_GET_Admin_Dashboard_Log_Login extends ACMS_GET
{
    function get()
    {
        if ( !sessionWithAdministration() ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('user');
        $SQL->addWhereOpr('user_login_datetime', null, '<>');
        $SQL->addWhereOpr('user_blog_id', BID);
        $SQL->setOrder('user_login_datetime', 'DESC');
        $SQL->setLimit(10);

        $q  = $SQL->get(dsn());

        if ( $DB->query($q, 'fetch') and $row = $DB->fetch($q) ) { do {
            $Tpl->add('auth:touch#'.$row['user_auth']);
            $Tpl->add('log:loop', array(
                'datetime'  => $row['user_login_datetime'],
                'name'      => $row['user_name'],
            ));
        } while( $row = $DB->fetch($q) ); } else {
            return '';
        }

        return $Tpl->get();
    }
}