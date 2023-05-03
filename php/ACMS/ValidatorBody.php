<?php

class ACMS_ValidatorBody
{
    function required($val)
    {
        $tmp = preg_replace('/^[\s　]*(.*?)[\s　]*$/u', '\1', $val ?? '');
        return !empty($tmp) or ('0' === $tmp);
    }

    function minlength($val, $arg)
    {
        if ( '' === $val || null === $val ) return true;
        return intval($arg) <= mb_strlen($val);
    }

    function maxlength($val, $arg)
    {
        if ( '' === $val || null === $val ) return true;
        return intval($arg) >= mb_strlen($val);
    }

    function min($val, $arg)
    {
        if ( '' === $val || null === $val ) return true;
        return intval($arg) <= intval($val);
    }

    function max($val, $arg)
    {
        if ( '' === $val || null === $val ) return true;
        return intval($arg) >= intval($val);
    }

    function regex($val, $regex)
    {
        if ( empty($regex) ) return false;
        if ( '' === $val || null === $val ) return true;

        //---------------
        // compatibility
        if ( '@' !== substr($regex, 0, 1) ) $regex  = '@'.$regex.'@';

        return preg_match($regex, $val) || multiBytePregMatch($regex . 'u', $val);
    }

    function regexp($val, $regexp)
    {
        $validator = new ACMS_Validator;
        return $validator->regex($val, $regexp);
    }

    function digits($val)
    {
        if ( '' === $val || null === $val) return true;
        return is_numeric($val);
    }

    function email($val)
    {
        if ( '' === $val || null === $val) return true;
        $ptn    = '/^(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*")(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*"))*@(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\])(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\]))*$/';
        return preg_match($ptn, $val);
    }

    function url($val)
    {
        if ( '' === $val || null === $val ) return true;
        return multiBytePregMatch('@^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$@u', $val);
    }

    function equalTo($val, $name, $Field)
    {
        return $val == $Field->get($name);
    }

    function dates($val)
    {
        if ( '' === $val || null === $val ) return true;
        $ptn    = '@^[sS]{1,2}(\d{2})\W{1}\d{1,2}\W{1}\d{0,2}$|^[hH]{1}(\d{1,2})\W{1}\d{1,2}\W{1}\d{0,2}$|^\d{1,2}$|^\d{1,2}\W{1}\d{1,2}$|^\d{2,4}\W{1}\d{1,2}\W{1}\d{0,2}$|^\d{4}\d{2}\d{2}$@';
        return preg_match($ptn, $val);
    }

    function times($val)
    {
        if ( '' === $val || null === $val ) return true;
        $ptn    = '@^\d{1,2}$|^\d{1,2}\W{1}\d{1,2}$|^\d{1,2}\W{1}\d{1,2}\W{1}\d{1,2}$|^\d{2}\d{2}\d{2}$@';
        return preg_match($ptn, $val);
    }

    function in($val, $choice)
    {
        if ( '' === $val || null === $val ) return true;
        if ( !is_array($choice) ) return false;
        return in_array($val, $choice);
    }

    function katakana($val)
    {
        if ( '' === $val || null === $val ) return true;
        if (multiBytePregMatch("/^[ァ-ヾー]+$/u", $val)) {
            return true;
        }
        return false;
    }

    function hiragana($val)
    {
      if ( '' === $val || null === $val ) return true;
      if (multiBytePregMatch("/^[ぁ-ゞー]+$/u", $val)) {
        return true;
      }
      return false;
    }

    function all_justChecked($ary, $cnt)
    {
        return intval($cnt) == count($ary);
    }

    function all_minChecked($ary, $min)
    {
        return intval($min) <= count($ary);
    }

    function all_maxChecked($ary, $max)
    {
        return intval($max) >= count($ary);
    }

    function all_unique($ary)
    {
		if ( ! is_array($ary) )return true;
        $_ary = array_unique($ary);
        return count($ary) === count($_ary);
    }

    function password($val)
    {
        if ('' === $val || null === $val) {
            return true;
        }
        $min = Config::get('password_validator_min', 0);
        $max = Config::get('password_validator_max', 9999);
        $uppercase = Config::get('password_validator_uppercase', 'off');
        $lowercase = Config::get('password_validator_lowercase', 'off');
        $digits = Config::get('password_validator_digits', 'off');
        $symbols = Config::get('password_validator_symbols', 'off');
        $type3 = Config::get('password_validator_3type', 'off');
        $blacklist = Config::get('password_validator_blacklist', '');
        $blacklist = preg_split("/[,\s\n]/", $blacklist);

        if ( !preg_match('/[!-~]*/', $val) ) {
            return false; // 不正な文字
        }
        if ( strlen($val) < intval($min) ) {
            return false;
        }
        if ( strlen($val) > intval($max) ) {
            return false;
        }
        $uppercase_check = preg_match('/[A-Z]/', $val);
        if ( $uppercase === 'on' && !$uppercase_check ) {
            return false;
        }
        $lowercase_check = preg_match('/[a-z]/', $val);
        if ( $lowercase === 'on' && !$lowercase_check ) {
            return false;
        }
        $digits_check = preg_match('/[0-9]/', $val);
        if ( $digits === 'on' && !$digits_check ) {
            return false;
        }
        $symbols_check = preg_match('/[!-\/:-@\[-`{-~]/', $val);
        if ( $symbols === 'on' && !$symbols_check ) {
            return false;
        }
        if ( $type3 === 'on' ) {
            $count = 0;
            if ( $uppercase_check ) $count++;
            if ( $lowercase_check ) $count++;
            if ( $digits_check ) $count++;
            if ( $symbols_check ) $count++;
            if ( $count < 3 ) {
                return false;
            }
        }
        if ( is_array($blacklist) && count($blacklist) > 0 ) {
            foreach ( $blacklist as $word ) {
                if ( empty($word) ) {
                    continue;
                }
                if ( strpos($val, $word) !== false ) {
                    return false;
                }
            }
        }
        return true;
    }
}
