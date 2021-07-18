<?php

class ACMS_GET_Shop_Transfer extends ACMS_GET_Shop
{
    function get()
    {	
        $this->initVars();

        if ( !$this->detectEdition() ) return $this->LICENSE_ERR_MSG;

        $SESSION =& $this->openSession();

        if ( !$SESSION->isNull('storeStep') ) {
            $step = $SESSION->get('storeStep');
            $SESSION->delete('storeStep');
        } else {
            $step = null;
        }

        if ( !$SESSION->isNull('storePage') ) {
            $page = $SESSION->get('storePage');
            $SESSION->delete('storePage');
            $this->screenTrans($page, $step);
        }

        /*-------------------------------------*/

        if ( !empty($_GET['step']) ) {
            $step = $_GET['step'];
        } else {
            $step = null;
        }

        if ( !empty($_GET['page']) ) {
            $page = $_GET['page'];
            $this->screenTrans($page, $step);
        }
    }
}