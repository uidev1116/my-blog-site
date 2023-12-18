<?php

class ACMS_GET_Admin_Fix_Ngram extends ACMS_GET_Admin_Fix
{
    function fix(& $Tpl, $block)
    {
        if ( !sessionWithAdministration() ) return false;

        $Fix    =& $this->Post->getChild('fix');
        $ngram  = $Fix->get('ngram');
        if ( empty($ngram) ) {
            $Fix->set('ngram', '2');
        }

        return true;
    }
}
