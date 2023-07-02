<?php

class ACMS_GET_Admin_Dashboard_Log_Access extends ACMS_GET
{
    function get()
    {
        if ( !sessionWithAdministration() ) return '';

        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $aryDate = array();
        for ( $i = 0; $i < 7; $i++ ) {
            $aryDate[] = date('Y-m-d', mktime(
                intval(date('H', REQUEST_TIME)), intval(date('i', REQUEST_TIME)), intval(date('s', REQUEST_TIME))
                , intval(date('m', REQUEST_TIME)), intval(date('d', REQUEST_TIME)) - $i, intval(date('Y', REQUEST_TIME))
            ));
        }

        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('log_access');
        $SQL->addSelect(SQL::newFunction('log_access_datetime', array('SUBSTR', 0, 10)), 'log_access_date');
        $SQL->addSelect('log_access_datetime', 'log_access_amount', null, 'count');
        $SQL->addWhereOpr('log_access_blog_id', BID);
        $SQL->addWhereIn(SQL::newFunction('log_access_datetime', array('SUBSTR', 0, 10)), $aryDate);
        $SQL->setGroup('log_access_date');
        $SQL->setLimit(10);
        $q = $SQL->get(dsn());

        $aryAmount = array();
        if ( $DB->query($q, 'fetch') ) {
            while ( $row = $DB->fetch($q) ) {
                $aryAmount[$row['log_access_date']] = intval($row['log_access_amount']);
            }
        }

        $amountMax = 0;
        foreach ( $aryDate as $date ) {
            $amount = isset($aryAmount[$date]) ? $aryAmount[$date] : 0;
            $Tpl->add('log:loop', array(
                'date' => $date,
                'amount' => $amount,
            ));
            if ( $amountMax < $amount ) $amountMax = $amount;
        }

        // グラフ用
        if ( !HTTPS ) {
            $aryAmountGraph = array();
            $lastDate = "";
            $firstDate = "";
            $halfDate = "";
            $i = 0;
            if ( $amountMax != 0 ) {
                $amountMax = ceil($amountMax / 100) * 100;
                $half = floor($amountMax / 2);

                foreach ( $aryDate as $date ) {
                    if ( $lastDate == "" ) {
                        $lastDate = preg_replace("/-/", ".", substr($date, 5, 5));
                    }
                    if ( $i == 3 ) {
                        $halfDate = preg_replace("/-/", ".", substr($date, 5, 5));
                    }
                    $aryAmountGraph[] = isset($aryAmount[$date]) ? ceil($aryAmount[$date] / $amountMax * 100) : 0;
                    $i++;
                    $firstDate = $date;
                }
                $firstDate = preg_replace("/-/", ".", substr($firstDate, 5, 5));
                krsort($aryAmountGraph);

                $Tpl->add(null, array(
                    'graphData' => implode(",", $aryAmountGraph),
                    'firstDate' => $firstDate,
                    'halfDate' => $halfDate,
                    'lastDate' => $lastDate,
                    'max' => $amountMax,
                    'half' => $half,
                ));
            }
        }
        return $Tpl->get();
    }
}