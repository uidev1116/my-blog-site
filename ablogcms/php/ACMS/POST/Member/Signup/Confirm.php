<?php

class ACMS_POST_Member_Signup_Confirm extends ACMS_POST_Member
{
    /**
     * @var boolean
     */
    protected $subscribeLoginAnywhere = false;

    /**
     * 会員登録の入力確認
     *
     * @return Field_Validation
     * @throws Exception
     */
    function post(): Field_Validation
    {
        $field = $this->extract('field', new ACMS_Validator());
        $user = $this->extract('user');
        if ('on' === config('subscribe_login_anywhere')) {
            $this->subscribeLoginAnywhere = true;
        }
        $this->validate($user);

        if (!$this->Post->isValidAll()) {
            $this->log($user, $field);
        }

        return $this->Post;
    }

    /**
     * ログをとる
     *
     * @param Field_Validation $user
     * @param Field_Validation $field
     * @return void
     */
    protected function log(Field_Validation $user, Field_Validation $field): void
    {
        $reasons = [];
        if (!$user->isValid('name', 'required')) {
            $reasons[] = '名前が指定されていません';
        }
        if (!$user->isValid('mail', 'required')) {
            $reasons[] = 'メールアドレスが指定されていません';
        }
        if (!$user->isValid('mail', 'email')) {
            $reasons[] = '不正なメールアドレスです';
        }
        if (!$user->isValid('pass', 'required')) {
            $reasons[] = 'パスワードが指定されていません';
        }
        if (!$user->isValid('pass', 'password')) {
            $reasons[] = 'パスワードのフォーマットが間違っています';
        }
        if (!$user->isValid('retype_pass', 'equalTo')) {
            $reasons[] = '入力されたパスワードが一致しません';
        }
        if (!$user->isValid('code', 'regex')) {
            $reasons[] = '不正なユーザーコードが指定されました';
        }
        if (!$user->isValid('mail_mobile', 'email')) {
            $reasons[] = '不正な形のモバイル用のメールアドレスです';
        }
        if (!$user->isValid('url', 'url')) {
            $reasons[] = '不正なURLが指定されました';
        }
        if (!$user->isValid('login', 'isOperable')) {
            $reasons[] = 'ログイン済み、もしくは会員機能が有効ではありません';
        }
        if (!$user->isValid('mail', 'doubleMail')) {
            $reasons[] = 'すでに存在するメールアドレスです';
        }
        if (!$user->isValid('code', 'doubleCode')) {
            $reasons[] = 'すでに存在するユーザーコードです';
        }
        AcmsLogger::info('会員登録申請のバリデートに失敗しました', [
            'reasons' => $reasons,
            'user' => $user->_aryField,
            'field' => $field,
        ]);
    }

    /**
     * 会員情報をバリデート
     *
     * @param Field_Validation $user
     * @return void
     */
    protected function validate(Field_Validation $user): void
    {
        $user->reset();
        $user->setMethod('name', 'required');
        $user->setMethod('mail', 'required');
        $user->setMethod('mail', 'email');
        $user->setMethod('code', 'regex', REGEX_VALID_ID);
        $user->setMethod('mail_mobile', 'email');
        $user->setMethod('url', 'url');
        $user->setMethod('login', 'isOperable', !SUID and 'on' == config('subscribe'));

        if (config('email-auth-signin') !== 'on') {
            // パスワードなしのメール認証によるサインイン設定
            $user->setMethod('pass', 'required');
            $user->setMethod('pass', 'password');
            $user->setMethod('retype_pass', 'equalTo', 'pass');
        }

        $AnywhereOrBid = SQL::newWhere();
        $AnywhereOrBid->addWhereOpr('user_login_anywhere', 'on', '=', 'OR');
        $AnywhereOrBid->addWhereOpr('user_blog_id', BID, '=', 'OR');

        if ($user->get('mail')) {
            $SQL = SQL::newSelect('user');
            $SQL->setSelect('user_id');
            $SQL->addWhereOpr('user_mail', $user->get('mail'));
            $SQL->addWhereOpr('user_status', 'pseudo', '<>');
            if (!$this->subscribeLoginAnywhere) {
                $SQL->addWhere($AnywhereOrBid);
            }
            $SQL->setLimit(1);
            $user->setMethod('mail', 'doubleMail', !DB::query($SQL->get(dsn()), 'one'));
        }
        if ($user->get('code')) {
            $SQL = SQL::newSelect('user');
            $SQL->setSelect('user_id');
            $SQL->addWhereOpr('user_code', $user->get('code'));
            $SQL->addWhereOpr('user_mail', $user->get('mail'), '<>');
            if (!$this->subscribeLoginAnywhere) {
                $SQL->addWhere($AnywhereOrBid);
            }
            $SQL->setLimit(1);
            $user->setMethod('code', 'doubleCode', !DB::query($SQL->get(dsn()), 'one'));
        }

        $user->validate(new ACMS_Validator());
    }
}
