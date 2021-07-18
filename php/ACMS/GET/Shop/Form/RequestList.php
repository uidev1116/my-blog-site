<?php

class ACMS_GET_Shop_Form_RequestList extends ACMS_GET_Shop
{
    function get()
    {
        $this->initVars();
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( !$this->detectEdition() ) return $this->LICENSE_ERR_MSG;
        $SESSION =& $this->openSession();

        /*
        * Date -------------------------------------------------------
        */
        $start  = intval(config('shop_order_request_date_min'));
        $limit  = intval(config('shop_order_request_date_max'));

        do {
            $date = date('Y-m-d', strtotime('+'.$start.'day'));
            $vars = array('date' => $date);

            // if session is stored
            if ( $SESSION->get('request_date') == $date ) {
                $vars += array('selected' => config('attr_selected'),
                               'checked'  => config('attr_checked'),
                               );
            }

            $Tpl->add('date:loop', $vars);
            $start++;
        } while ( $start <= $limit );

        /*
        * Time -------------------------------------------------------
        */
        $times = configArray('shop_order_request_time');

        foreach ( $times as $time ) {
            $vars = array('time' => $time);

            // if session is stored
            if ( $SESSION->get('request_time') == $time ) {
                $vars += array('selected' => config('attr_selected'),
                               'checked'  => config('attr_checked'),
                               );
            }

            $Tpl->add('time:loop', $vars);
        }

        /*
        * Others ----------------------------------------------------
        */
        $requests = configArray('shop_order_request_others_label');
        $charge   = configArray('shop_order_request_others_charge');

        foreach ( $requests as $key => $request ) {
            $vars = array(
                        'request' => $request,
                        'charge'  => @$charge[$key],
                        );

            // if session is stored
            if ( $SESSION->get('request_others') == $request ) {
                $vars += array('selected' => config('attr_selected'),
                               'checked'  => config('attr_checked'),
                               );
            }

            $Tpl->add('request:loop', $vars);
        }

        return $Tpl->get();
    }
}