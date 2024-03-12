<?php

class ACMS_Validator_Comment extends ACMS_Validator
{
    function auth($val)
    {
        if (empty($val)) {
            return false;
        }
        return (ACMS_RAM::commentPass(CMID) == $val);
    }

    function blackList($val)
    {
        if (empty($val)) {
            return true;
        }
        $flag   = true;
        if ($blacklist = config('comment_black_list')) {
            foreach (preg_split(REGEXP_SEPARATER, $blacklist, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                if (!$word = trim($word)) {
                    continue;
                }
                if (!is_int(strpos($val, $word))) {
                    continue;
                }
                $flag   = false;
                break;
            }
        }
        return $flag;
    }

    function passCheck($val)
    {
        if (!CMID) {
            return false;
        }
        $DB     = DB::singleton(dsn());

        if (sessionWithCompilation()) {
            return true;
        } elseif (!!SUID && ACMS_RAM::entryUser(EID) == SUID) {
            return true;
        } elseif (!!SUID && ACMS_RAM::commentUser(CMID) == SUID) {
            return true;
        } elseif (!empty($val)) {
            $SQL    = SQL::newSelect('comment');
            $SQL->setSelect('comment_id');
            $SQL->addWhereOpr('comment_pass', $val);
            $SQL->addWhereOpr('comment_id', CMID);
            return !!$DB->query($SQL->get(dsn()), 'one');
        } elseif (!!SUID && $suid = intval(SUID)) {
            $SQL    = SQL::newSelect('comment');
            $SQL->setSelect('comment_id');
            $SQL->addWhereOpr('comment_user_id', $suid);
            $SQL->addWhereOpr('comment_id', CMID);
            return !!$DB->query($SQL->get(dsn()), 'one');
        } else {
            return false;
        }
    }
}

class ACMS_POST_Comment extends ACMS_POST
{
    function & extractComment()
    {
        $Comment = $this->extract('comment');
        $Comment->setMethod('name', 'required');
        $Comment->setMethod('title', 'required');
        $Comment->setMethod('body', 'required');
        $Comment->setMethod('pass', 'required');
        $Comment->setMethod('mail', 'email');
        $Comment->setMethod('url', 'url');
        $Comment->setMethod('title', 'blackList');
        $Comment->setMethod('pass', 'regex', REGEX_VALID_PASSWD);
        $Comment->validate(new ACMS_Validator_Comment());

        return $Comment;
    }

    function validatePassword($Comment = null)
    {
        if ($Comment === null) {
            $Comment = $this->extract('comment');
        }
        $key = !!$Comment->get('old_pass') ? 'old_pass' : 'pass';
        $pass = $Comment->get($key);

        $Validation = new Field_Validation();
        $Validation->setField($key, $pass);
        $Validation->setMethod($key, 'passCheck');
        $Validation->validate(new ACMS_Validator_Comment());

        return $Validation->isValid();
    }
}
