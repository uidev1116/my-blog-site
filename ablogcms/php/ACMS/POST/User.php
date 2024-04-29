<?php

class ACMS_POST_User extends ACMS_POST
{
    protected function isLimit($update = false)
    {
        if (!IS_LICENSED) {
            return false;
        }
        $amount = $this->countOfLimitedAuthUsers();

        return $update ? (LICENSE_BLOG_LIMIT >= $amount) : (LICENSE_BLOG_LIMIT > $amount);
    }

    /**
     * 現在の制限数対象のユーザー数を取得する
     * @return int
     */
    protected function countOfLimitedAuthUsers()
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('user');
        $SQL->setSelect('user_id', 'user_amount', null, 'count');
        $SQL->addWhereIn('user_auth', ['administrator', 'editor', 'contributor']);
        return  intval($DB->query($SQL->get(dsn()), 'one'));
    }
}

class ACMS_Validator_User extends ACMS_Validator
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
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('user');
        $SQL->addWhereOpr('user_blog_id', BID);
        $SQL->addWhereOpr('user_id', $uid);

        $all = $DB->query($SQL->get(dsn()), 'all');
        $all = array_filter($all, function ($user) use ($pass) {
            return acmsUserPasswordVerify($pass, $user['user_pass'], getPasswordGeneration($user));
        });
        if (empty($all)) {
            return false;
        }
        return true;
    }
}
