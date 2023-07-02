<?php

class ACMS_GET_PasswordPolicy extends ACMS_GET
{
    function get()
    {
        $Tpl  = new Template($this->tpl, new ACMS_Corrector());

        $min = Config::get('password_validator_min', 0);
        $max = Config::get('password_validator_max', 9999);
        $uppercase = Config::get('password_validator_uppercase', 'off');
        $lowercase = Config::get('password_validator_lowercase', 'off');
        $digits = Config::get('password_validator_digits', 'off');
        $symbols = Config::get('password_validator_symbols', 'off');
        $type3 = Config::get('password_validator_3type', 'off');
        $blacklist = Config::get('password_validator_blacklist', '');
        $blacklist = preg_split("/[,\s\n]/", $blacklist);

        if ( intval($min) > 0 ) {
            $Tpl->add('min', array(
                'num' => $min,
            ));
        }
        if ( intval($max) < 9999 ) {
            $Tpl->add('max', array(
                'num' => $max,
            ));
        }
        if ( $uppercase === 'on' ) {
            $Tpl->add('uppsercase');
        }
        if ( $lowercase === 'on' ) {
            $Tpl->add('lowercase');
        }
        if ( $digits === 'on' ) {
            $Tpl->add('digits');
        }
        if ( $symbols === 'on' ) {
            $Tpl->add('symbols');
        }
        if ( $type3 === 'on' ) {
            $Tpl->add('type3');
        }
        if ( is_array($blacklist) && count($blacklist) > 0 ) {
            foreach ( $blacklist as $word ) {
                if ( empty($word) ) {
                    continue;
                }
                $Tpl->add('blacklist:loop', array(
                    'word' => $word,
                ));
            }
        }
        return $Tpl->get();
    }
}