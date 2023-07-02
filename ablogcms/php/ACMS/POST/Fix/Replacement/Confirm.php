<?php

class ACMS_POST_Fix_Replacement_Confirm extends ACMS_POST_Fix
{
    function post()
    {
        if ( !sessionWithAdministration() ) return false;

        $Fix = $this->extract('fix');
        $Fix->setMethod('fix_replacement_target', 'required');
        $Fix->setMethod('fix_replacement_pattern', 'required');
        $Fix->setMethod('fix_replacement_replacement', 'required');
        $Fix->validate(new ACMS_Validator());

        if ( $Fix->isValidAll() ) {
            $this->Post->set('step', 'confirm');
        }

        return $this->Post;
    }
}
