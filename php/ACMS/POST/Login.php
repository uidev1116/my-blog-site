<?php

class ACMS_Validator_Login extends ACMS_Validator
{
    public function exist($mail)
    {
        if (empty($mail)) {
            return true;
        }
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('user');
        $SQL->setSelect('user_id');
        $SQL->addWhereOpr('user_status', 'open');
        $SQL->addWhereOpr('user_mail', $mail);
        $SQL->addWhereOpr('user_blog_id', BID);

        return !!$DB->query($SQL->get(dsn()), 'one');
    }

    public function confirmed($mail)
    {
        if (empty($mail)) {
            return true;
        }
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('user');
        $SQL->setSelect('user_confirmation_token');
        $SQL->addWhereOpr('user_mail', $mail);
        $SQL->addWhereOpr('user_blog_id', BID);

        // 取得したtokenがfaltyな値であればtrueを返す
        // 空文字の場合： メール認証済みなのでok
        // falseの場合：ユーザーが存在しない場合、メール認証済みかどうかは判定しないのでok
        // 空文字以外の文字列：メール未認証なのでng
        return !$DB->query($SQL->get(dsn()), 'one');
    }
}

class ACMS_POST_Login extends ACMS_POST
{
    var $isCacheDelete  = false;
}
