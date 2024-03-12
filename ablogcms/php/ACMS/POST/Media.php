<?php

class ACMS_POST_Media extends ACMS_POST
{
    function isLimit($update = false)
    {
        if (!IS_LICENSED) {
            return false;
        }
        $amount = $this->countOfLimitedAuthUsers();

        return $update ? (LICENSE_BLOG_LIMIT >= $amount) : (LICENSE_BLOG_LIMIT > $amount);
    }

    function countOfLimitedAuthUsers()
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('user');
        $SQL->setSelect('user_id', 'user_amount', null, 'count');
        $SQL->addWhereIn('user_auth', array('administrator', 'editor', 'contributor'));
        return  intval($DB->query($SQL->get(dsn()), 'one'));
    }
}

class ACMS_Validator_Media extends ACMS_Validator
{
    function doubleCode($code, $uid = null)
    {
        $bid    = BID;
        if (is_array($uid)) {
            $uid    = !empty($uid['uid']) ? $uid['uid'] : null;
            $bid    = !empty($uid['bid']) ? $uid['bid'] : null;
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('user');
        $SQL->setSelect('user_id');
        $SQL->addWhereOpr('user_code', $code);
        if (!empty($bid)) {
            $anywhereOrBid  = SQL::newWhere();
            $anywhereOrBid->addWhereOpr('user_login_anywhere', 'on', '=', 'OR');
            $anywhereOrBid->addWhereOpr('user_blog_id', $bid, '=', 'OR');
            $SQL->addWhere($anywhereOrBid);
        }
        if (!empty($uid)) {
            $SQL->addWhereOpr('user_id', $uid, '<>');
        }
        $SQL->setLimit(1);
        return !$DB->query($SQL->get(dsn()), 'one');
    }

    function doubleMail($mail, $uid = null)
    {
        $bid    = BID;
        if (is_array($uid)) {
            $uid    = !empty($uid['uid']) ? $uid['uid'] : null;
            $bid    = !empty($uid['bid']) ? $uid['bid'] : null;
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('user');
        $SQL->setSelect('user_id');
        $SQL->addWhereOpr('user_mail', $mail);
        if (!empty($bid)) {
            $anywhereOrBid  = SQL::newWhere();
            $anywhereOrBid->addWhereOpr('user_login_anywhere', 'on', '=', 'OR');
            $anywhereOrBid->addWhereOpr('user_blog_id', $bid, '=', 'OR');
            $SQL->addWhere($anywhereOrBid);
        }
        if (!empty($uid)) {
            $SQL->addWhereOpr('user_id', $uid, '<>');
        }
        $SQL->setLimit(1);
        return !$DB->query($SQL->get(dsn()), 'one');
    }

    function oldPass($pass, $uid)
    {
        if (empty($uid)) {
            return false;
        }
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('user');
        $SQL->setSelect('user_id');
        $SQL->addWhereOpr('user_pass', md5($pass));
        $SQL->addWhereOpr('user_blog_id', BID);
        $SQL->addWhereOpr('user_id', $uid);
        return !!$DB->query($SQL->get(dsn()), 'one');
    }
}
