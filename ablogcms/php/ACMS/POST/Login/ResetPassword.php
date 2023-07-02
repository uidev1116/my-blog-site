<?php

class ACMS_POST_Login_ResetPassword extends ACMS_POST_Login
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
     * @return Field
     */
    public function post()
    {
        $User = $this->extract('user');
        $Login = $this->extract('login');
        $context = $this->validateResetUrl();
        $user = $this->validate($User, $context);

        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }
        $uid = $user['user_id'];
        $this->updatePassword($uid, $User->get('pass'));
        if (Tfa::isAvailableAccount($uid)) {
            $Login->set('reset', 'success');
            return $this->Post;
        }

        // ログイン日時更新
        $sql = SQL::newUpdate('user');
        $sql->addUpdate('user_pass_reset', '');
        $sql->addUpdate('user_login_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $sql->addWhereOpr('user_id', $uid);
        DB::query($sql->get(dsn()), 'exec');

        $sid = generateSession($user);
        $url = acmsLink(array(
            'bid' => BID,
            'sid' => $sid,
            'query' => array(),
        ));
        $this->redirect($url, $sid, true);
    }

    /**
     * @param Field $User
     * @param array $context
     * @return array | boolean
     */
    protected function validate($User, $context)
    {
        $isOperable = true;
        $User->reset();
        $User->setMethod('pass', 'required');
        $User->setMethod('pass', 'password');
        $User->setMethod('retype_pass', 'equalTo', 'pass');
        if (!isset($context['email']) || !isset($context['token'])) {
            $isOperable = false;
        }
        $sql = SQL::newSelect('user');
        $sql->addWhereOpr('user_status', 'open');
        $sql->addWhereOpr('user_mail', $context['email']);
        $sql->addWhereOpr('user_reset_password_token', $context['token']);
        $user = DB::query($sql->get(dsn()), 'row');
        if (empty($user)) {
            $isOperable = false;
        }
        $User->setMethod('reset', 'isOperable', !SUID && $isOperable);
        $User->validate(new ACMS_Validator());

        return $user;
    }

    /**
     * @return bool | int
     */
    protected function validateResetUrl()
    {
        $key = $this->Get->get('key');
        $salt = $this->Get->get('salt');
        $context = $this->Get->get('context');

        try {
            $context = Login::validateTimedLinkParams($key, $salt, $context);
            return $context;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $uid
     * @param $newPassword
     */
    protected function updatePassword($uid, $newPassword)
    {
        $sql = SQL::newUpdate('user');
        $sql->addUpdate('user_pass', acmsUserPasswordHash($newPassword));
        $sql->addUpdate('user_pass_generation', PASSWORD_ALGORITHM_GENERATION);
        $sql->addUpdate('user_reset_password_token', '');
        $sql->addWhereOpr('user_id', $uid);
        DB::query($sql->get(dsn()), 'exec');
        ACMS_RAM::user($uid, null);
    }
}
