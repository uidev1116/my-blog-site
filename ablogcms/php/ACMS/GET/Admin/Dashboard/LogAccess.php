<?php

class ACMS_GET_Admin_Dashboard_LogAccess extends ACMS_GET
{
    function get()
    {
        if ( !sessionWithAdministration() ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newselect('log_access');
        $SQL->addSelect('log_access_datetime', 'log_access_amount', null , 'count');
        $SQL->addWhereOpr('log_access_blog_id', BID);
        $q = $SQL->get(dsn());
        $amountAll = $DB->query($q, 'one');

        $SQL    = SQL::newselect('log_access');
        $SQL->addSelect('log_access_datetime', 'log_access_amount', null , 'count');
        $SQL->addWhereOpr('log_access_publishing', 'dynamic');
        $SQL->addWhereOpr('log_access_blog_id', BID);
        $q = $SQL->get(dsn());
        $amountDyn = $DB->query($q, 'one');

        $SQL    = SQL::newselect('log_access');
        $SQL->addSelect('log_access_datetime', 'log_access_amount', null , 'count');
        $SQL->addWhereOpr('log_access_publishing', 'static');
        $SQL->addWhereOpr('log_access_blog_id', BID);
        $q = $SQL->get(dsn());
        $amountSta = $DB->query($q, 'one');

        $SQL    = SQL::newselect('log_access');
        $SQL->addSelect('log_access_datetime', 'log_access_amount', null , 'count');
        $SQL->addWhereOpr('log_access_publishing', 'false');
        $SQL->addWhereOpr('log_access_blog_id', BID);
        $q = $SQL->get(dsn());
        $amountFal = $DB->query($q, 'one');

        $vars['amount_all']     = $amountAll;
        $vars['amount_dynamic'] = $amountDyn;
        $vars['amount_static']  = $amountSta;
        $vars['amount_false']   = $amountFal;

        $SQL    = SQL::newSelect('log_access');
        $SQL->addSelect(SQL::newFunction('log_access_datetime', array('SUBSTR', 0, 7)), 'log_year_month');
        $SQL->addGroup('log_year_month');
        $SQL->addOrder('log_year_month', 'DESC');
        $SQL->addWhereOpr('log_access_blog_id', BID);
        $yms    = $DB->query($SQL->get(dsn()), 'all');

        foreach ( $yms as $i => $ym ) {
            $vals = array(
                'date'  => $ym['log_year_month'],
                'start' => date('Y-m-01 00:00:00', strtotime($ym['log_year_month'])),
            );
//            if ( $i === 0 ) $vals['selected'] = config('attr_selected');
            $Tpl->add('start:loop', $vals);
        }

        foreach ( $yms as $i => $ym ) {
            $vals = array(
                'date'  => $ym['log_year_month'],
                'end'   => date('Y-m-t 23:59:59', strtotime($ym['log_year_month'])),
            );
//            if ( $i === (count($yms)-1) ) $vals['selected'] = config('attr_selected');
            $Tpl->add('end:loop', $vals);
        }

        if ( $this->Post->isExists('term_not_selected') ) {
            $Tpl->add('term_not_selected');
        }
        if ( $this->Post->isExists('archives_not_writable') ) {
            $Tpl->add('archives_not_writable');
        }
        switch ( config('log_access') ) {
            case 'on' :
                $Tpl->add('log_access_enable');
                break;
            case 'post' :
                $Tpl->add('log_access_post_enable');
                break;
            case 'off' :
                $Tpl->add('log_access_disable');
                break;
            default :
                $Tpl->add('log_access_disable');
        }

        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
