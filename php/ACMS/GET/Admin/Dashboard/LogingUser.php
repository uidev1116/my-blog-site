<?php

class ACMS_GET_Admin_Dashboard_LogingUser extends ACMS_GET
{
    function get()
    {
        if ( !sessionWithSubscription() ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();
        
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('session');
        $SQL->addLeftJoin('user', 'user_id', 'session_user_id');
        $SQL->addSelect('user_name');
        $SQL->addSelect('user_auth');
        $SQL->addWhereOpr('session_blog_id', BID);
        $q = $SQL->get(dsn());
        
        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                $Tpl->add('auth:touch#'.$row['user_auth']);
                $Tpl->add('user:loop',array(
                                        'name' => $row['user_name'],
                                        ));
            } while ($row = $DB->fetch($q));
        } else {
            return '';
        }

        return $Tpl->get();
    }
}