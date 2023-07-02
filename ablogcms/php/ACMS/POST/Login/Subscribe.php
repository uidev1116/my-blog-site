<?php

class ACMS_POST_Login_Subscribe extends ACMS_POST_Login
{
    /**
     * CSRF対策
     *
     * @var bool
     */
    protected $isCSRF = true;

    /**
     * 二重送信対策
     *
     * @var bool
     */
    protected $checkDoubleSubmit = true;

    /**
     * @var boolean
     */
    protected $subscribeLoginAnywhere = false;

    /**
     * Run
     *
     * @return bool|Field
     * @throws Exception
     */
    function post()
    {
        $this->cleanUpUser();

        $Field = $this->extract('field', new ACMS_Validator());
        $User = $this->extract('user');

        if ('on' === config('subscribe_login_anywhere')) {
            $this->subscribeLoginAnywhere = true;
        }
        $this->validate($User);

        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }
        $uid = $this->findUser($User);
        $token = Common::genPass(32);

        if (empty($uid)) {
            // ユーザー作成
            $uid = $this->createUser($User, $token);
        } else {
            // すでに同じメールアドレスのユーザーがいれば、パスワードを更新して、承認リンクを再発行する
            $this->updateUser($uid, $User, $token);
        }

        Common::saveField('uid', $uid, $Field);
        Common::saveFulltext('uid', $uid, Common::loadUserFulltext($uid));

        $lifetime = intval(config('user_activation_url_lifetime', 30)) * 60;
        $User->set('uid', $uid);
        $isSend = $this->send($User, $Field, $this->buildAuthUrl($User, $token, $lifetime));

        if (!$isSend) {
            $User->setMethod('mail', 'send', false);
            $User->validate(new ACMS_Validator());
        }
        return $this->Post;
    }

    /**
     * 有効期限切れのユーザーを削除
     */
    protected function cleanUpUser()
    {
        $DB = DB::singleton(dsn());
        $ExpireUser = SQL::newWhere();
        $ExpireUser->addWhereOpr('user_confirmation_token', '', '<>');
        $ExpireUser->addWhereOpr('user_generated_datetime',
            date('Y-m-d H:i:s', REQUEST_TIME - intval(config('subscribe_expire'))), '<');

        $User = SQL::newSelect('user');
        $User->addSelect('user_id');
        $User->addWhere($ExpireUser);

        $SQL = SQL::newDelete('field');
        $SQL->addWhereIn('field_uid', $User);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL = SQL::newDelete('user');
        $SQL->addWhere($ExpireUser);
        $DB->query($SQL->get(dsn()), 'exec');
    }

    /**
     * 登録ユーザーを検索
     *
     * @param Field $User
     * @return int
     */
    protected function findUser($User)
    {
        $SQL = SQL::newSelect('user');
        $SQL->setSelect('user_id');
        $SQL->addWhereOpr('user_mail', $User->get('mail'));
        $SQL->addWhereOpr('user_blog_id', BID);
        $SQL->setLimit(1);
        return intval(DB::query($SQL->get(dsn()), 'one'));
    }

    /**
     * @param $User
     */
    protected function validate(& $User)
    {
        $DB = DB::singleton(dsn());

        $User->reset();
        $User->setMethod('name', 'required');
        $User->setMethod('mail', 'required');
        $User->setMethod('mail', 'email');
        $User->setMethod('pass', 'required');
        $User->setMethod('pass', 'password');
        $User->setMethod('retype_pass', 'equalTo', 'pass');

        $User->setMethod('code', 'regex', REGEX_VALID_ID);
        $User->setMethod('mail_mobile', 'email');
        $User->setMethod('url', 'url');
        $User->setMethod('login', 'isOperable', !SUID and 'on' == config('subscribe'));

        $AnywhereOrBid = SQL::newWhere();
        $AnywhereOrBid->addWhereOpr('user_login_anywhere', 'on', '=', 'OR');
        $AnywhereOrBid->addWhereOpr('user_blog_id', BID, '=', 'OR');

        if ($User->get('mail')) {
            $SQL = SQL::newSelect('user');
            $SQL->setSelect('user_id');
            $SQL->addWhereOpr('user_mail', $User->get('mail'));
            $SQL->addWhereOpr('user_confirmation_token', '');
            if (!$this->subscribeLoginAnywhere) {
                $SQL->addWhere($AnywhereOrBid);
            }
            $SQL->setLimit(1);

            $User->setMethod('mail', 'doubleMail', !$DB->query($SQL->get(dsn()), 'one'));
        }

        if ($User->get('code')) {
            $SQL = SQL::newSelect('user');
            $SQL->setSelect('user_id');
            $SQL->addWhereOpr('user_code', $User->get('code'));
            $SQL->addWhereOpr('user_mail', $User->get('mail'), '<>');
            if (!$this->subscribeLoginAnywhere) {
                $SQL->addWhere($AnywhereOrBid);
            }
            $SQL->setLimit(1);
            $User->setMethod('code', 'doubleCode', !$DB->query($SQL->get(dsn()), 'one'));
        }

        $User->validate(new ACMS_Validator());
    }

    /**
     * @param Field $User
     * @param string $token
     *
     * @return int $uid
     */
    protected function createUser($User, $token)
    {
        $uid = DB::query(SQL::nextval('user_id', dsn()), 'seq');
        $auth = config('subscribe_auth', 'subscriber');

        $SQL = SQL::newSelect('user');
        $SQL->setSelect('user_sort');
        $SQL->setOrder('user_sort', 'DESC');
        $SQL->addWhereOpr('user_blog_id', BID);
        $sort = intval(DB::query($SQL->get(dsn()), 'one')) + 1;

        $SQL = SQL::newInsert('user');
        $SQL->addInsert('user_id', $uid);
        $SQL->addInsert('user_sort', $sort);
        $SQL->addInsert('user_blog_id', BID);
        $SQL->addInsert('user_status', config('subscribe_init_status', 'open'));
        $SQL->addInsert('user_name', $User->get('name'));
        $SQL->addInsert('user_mail', $User->get('mail'));
        $SQL->addInsert('user_mail_mobile', $User->get('mail_mobile'));
        if ($User->get('mail_magazine') === 'off') {
            $SQL->addInsert('user_mail_magazine', 'off');
        }
        if ($User->get('mail_mobile_magazine') === 'off') {
            $SQL->addInsert('user_mail_mobile_magazine', 'off');
        }
        $SQL->addInsert('user_code', $User->get('code'));
        $SQL->addInsert('user_url', $User->get('url'));
        $SQL->addInsert('user_auth', $auth);
        $SQL->addInsert('user_indexing', 'on');
        $SQL->addInsert('user_pass', acmsUserPasswordHash($User->get('pass')));
        $SQL->addInsert('user_pass_generation', PASSWORD_ALGORITHM_GENERATION);
        if (config('subscribe_activation') !== 'off') {
            $SQL->addInsert('user_confirmation_token', $token);
        }
        if ($this->subscribeLoginAnywhere) {
            $SQL->addInsert('user_login_anywhere', 'on');
        }
        $SQL->addInsert('user_generated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        DB::query($SQL->get(dsn()), 'exec');

        return $uid;
    }

    /**
     * @param int $uid
     * @param string $token
     * @param Field $User
     */
    protected function updateUser($uid, $User, $token)
    {
        $SQL = SQL::newUpdate('user');
        $SQL->addUpdate('user_name', $User->get('name'));
        $SQL->addUpdate('user_mail_mobile', $User->get('mail_mobile'));
        $SQL->addUpdate('user_code', $User->get('code'));
        $SQL->addUpdate('user_url', $User->get('url'));
        $SQL->addUpdate('user_pass', acmsUserPasswordHash($User->get('pass')));
        $SQL->addUpdate('user_confirmation_token', $token);
        $SQL->addUpdate('user_generated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $SQL->addWhereOpr('user_id', $uid);
        if ($this->subscribeLoginAnywhere) {
            $SQL->addUpdate('user_login_anywhere', 'on');
        }
        DB::query($SQL->get(dsn()), 'exec');
        ACMS_RAM::user($uid, null);
    }

    /**
     * @param Field $User
     * @param Field $Field
     * @param string $authUrl
     * @return bool
     * @throws Exception
     */
    protected function send($User, $Field, $authUrl)
    {
        $Field->setField('uid', $User->get('uid'));
        $Field->setField('name', $User->get('name'));
        $Field->setField('mail', $User->get('mail'));
        $Field->setField('code', $User->get('code'));
        $Field->setField('mail_mobile', $User->get('mail_mobile'));
        $Field->setField('url', $User->get('url'));
        $Field->setField('subscribeUrl', $authUrl);

        $isSend = false;
        if (1
            and $to = $User->get('mail')
            and $subjectTpl = findTemplate(config('mail_subscribe_tpl_subject'))
            and $bodyTpl = findTemplate(config('mail_subscribe_tpl_body'))
        ) {
            $subject = Common::getMailTxt($subjectTpl, $Field);
            $body = Common::getMailTxt($bodyTpl, $Field);

            try {
                $mailer = Mailer::init();
                $mailer = $mailer->setFrom(config('mail_subscribe_from'))
                    ->setTo($to)
                    ->setBcc(implode(', ', configArray('mail_subscribe_bcc')))
                    ->setSubject($subject)
                    ->setBody($body);

                if ($bodyHtmlTpl = findTemplate(config('mail_subscribe_tpl_body_html'))) {
                    $bodyHtml = Common::getMailTxt($bodyHtmlTpl, $Field);
                    $mailer = $mailer->setHtml($bodyHtml);
                }
                $mailer->send();

                $isSend = true;

                if (1
                    and $to = configArray('mail_subscribe_admin_to')
                    and $subjectTpl = findTemplate(config('mail_subscribe_admin_tpl_subject'))
                    and $bodyTpl = findTemplate(config('mail_subscribe_admin_tpl_body'))
                ) {
                    $subject = Common::getMailTxt($subjectTpl, $Field);
                    $body = Common::getMailTxt($bodyTpl, $Field);

                    $mailer = Mailer::init();
                    $mailer = $mailer->setFrom(config('mail_subscribe_admin_from'))
                        ->setTo(implode(', ', $to))
                        ->setBcc(implode(', ', configArray('mail_subscribe_admin_bcc')))
                        ->setSubject($subject)
                        ->setBody($body);

                    if ($bodyHtmlTpl = findTemplate(config('mail_subscribe_admin_tpl_body_html'))) {
                        $bodyHtml = Common::getMailTxt($bodyHtmlTpl, $Field);
                        $mailer = $mailer->setHtml($bodyHtml);
                    }
                    $mailer->send();
                }
            } catch (Exception $e) {
                throw $e;
            }
        }
        return $isSend;
    }

    /**
     * @param Field $User
     * @param string $token
     * @param int $lifetime
     * @return string
     */
    protected function buildAuthUrl($User, $token, $lifetime = 3600)
    {
        $uri = acmsLink(array(
            'protocol' => SSL_ENABLE ? 'https' : 'http',
            'bid' => BID,
            'login' => true,
        ), false);

        $params = Login::createTimedLinkParams(array(
            'email' => $User->get('mail'),
            'token' => $token,
        ), $lifetime);

        return "{$uri}?type=subscribe&{$params}";
    }
}
