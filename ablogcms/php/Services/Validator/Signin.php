<?php

namespace Acms\Services\Validator;

use ACMS_Validator;
use DB;
use SQL;

class Signin extends ACMS_Validator
{
    /**
     * メールアドレスが存在するか確認
     *
     * @param string|null $email
     * @return bool
     */
    public function exist(?string $email): bool
    {
        if (empty($email)) {
            return true;
        }
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereIn('user_status', ['open', 'pseudo']);
        $sql->addWhereOpr('user_mail', $email);
        $sql->addWhereOpr('user_blog_id', BID);

        return !!DB::query($sql->get(dsn()), 'one');
    }

    /**
     *　メールアドレスが認証済みか確認
     *
     * @param string|null $email
     * @return bool
     */
    public function confirmed(?string $email): bool
    {
        if (empty($email)) {
            return true;
        }
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereOpr('user_status', 'pseudo');
        $sql->addWhereOpr('user_mail', $email);
        $sql->addWhereOpr('user_blog_id', BID);

        return !DB::query($sql->get(dsn()), 'one');
    }

    /**
     * メールアドレスがすでに存在しないか確認
     *
     * @param null|string $email
     * @param null|int|string $uid
     * @return bool
     */
    public function doubleMail(?string $email, $uid = null): bool
    {
        if (empty($email)) {
            return true;
        }
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereOpr('user_mail', $email);
        if (!empty($uid)) {
            $sql->addWhereOpr('user_id', $uid, '<>');
        }
        $sql->setLimit(1);

        return !DB::query($sql->get(dsn()), 'one');
    }

    /**
     * ユーザーID/コードがすでに存在しないか確認
     *
     * @param null|string $code
     * @param null|int|string $uid
     * @return bool
     */
    public function doubleCode(?string $code, $uid = null): bool
    {
        if (empty($code)) {
            return true;
        }
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereOpr('user_code', $code);
        if (!empty($uid)) {
            $sql->addWhereOpr('user_id', $uid, '<>');
        }
        $sql->setLimit(1);

        return !DB::query($sql->get(dsn()), 'one');
    }

    /**
     * 古いパスワードか判定
     *
     * @param null|string $pass
     * @param null|int $uid
     * @return bool
     */
    public function oldPass(?string $pass, ?int $uid)
    {
        if (empty($uid)) {
            return false;
        }
        $sql = SQL::newSelect('user');
        $sql->addWhereOpr('user_id', $uid);

        $all = DB::query($sql->get(dsn()), 'all');
        $all = array_filter($all, function ($user) use ($pass) {
            return acmsUserPasswordVerify($pass, $user['user_pass'], getPasswordGeneration($user));
        });
        if (empty($all)) {
            return false;
        }
        return true;
    }
}
