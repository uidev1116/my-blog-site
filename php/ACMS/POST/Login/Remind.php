<?php

class ACMS_POST_Login_Remind extends ACMS_POST_Login
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
     * @throws Exception
     */
    public function post()
    {
        $this->Post->set('login', array('mail', 'To'));
        $this->Post->set('To', '&mail;');
        $Login = $this->extract('login');
        $this->validate($Login);

        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }

        $token = Common::genPass(32);
        $lifetime = intval(config('password_reset_url_lifetime', 30)) * 60;
        $resetUrl = $this->buildResetPasswordUrl($Login->get('mail'), $token, $lifetime);
        $isSend = $this->send($Login, $token, $resetUrl);

        if (!$isSend) {
            $Login->setMethod('mail', 'send', false);
            $Login->validate(new ACMS_Validator_Login());
        }
        return $this->Post;
    }

    /**
     * @param Field $Login
     */
    protected function validate($Login)
    {
        $Login->setMethod('login', 'sessionAlready', !SUID);
        $Login->setMethod('mail', 'required');
        $Login->setMethod('mail', 'exist');
        $Login->setMethod('mail', 'confirmed');
        $Login->validate(new ACMS_Validator_Login());
    }

    /**
     * @param string $email
     * @param string $token
     * @param int $lifetime
     * @return string
     */
    protected function buildResetPasswordUrl($email, $token, $lifetime = 3600)
    {
        $uri = acmsLink(array(
            'protocol' => SSL_ENABLE ? 'https' : 'http',
            'bid' => BID,
            'login' => true,
        ), false);

        $params = Login::createTimedLinkParams(array(
            'email' => $email,
            'token' => $token,
        ), $lifetime);

        return "{$uri}?type=reset&{$params}";
    }

    /**
     * @param Field $Login
     * @param string $token
     * @param string $resetUrl
     * @return bool
     * @throws Exception
     */
    protected function send($Login, $token, $resetUrl)
    {
        $isSend = false;
        $Login->setField('resetUrl', $resetUrl);

        if (1
            and $to = $Login->getArray('To')
            and $subjectTpl = findTemplate(config('mail_remind_tpl_subject'))
            and $bodyTpl = findTemplate(config('mail_remind_tpl_body'))
        ) {
            $subject = Common::getMailTxt($subjectTpl, $Login);
            $body = Common::getMailTxt($bodyTpl, $Login);

            try {
                $mailer = Mailer::init();
                $mailer = $mailer->setFrom(config('mail_remind_from'))
                    ->setTo(implode(', ', $to))
                    ->setBcc(implode(', ', configArray('mail_remind_bcc')))
                    ->setSubject($subject)
                    ->setBody($body);

                if ($bodyHtmlTpl = findTemplate(config('mail_remind_tpl_body_html'))) {
                    $bodyHtml = Common::getMailTxt($bodyHtmlTpl, $Login);
                    $mailer = $mailer->setHtml($bodyHtml);
                }
                $mailer->send();

                $DB = DB::singleton(dsn());
                $SQL = SQL::newUpdate('user');
                $SQL->setUpdate('user_reset_password_token', $token);
                $SQL->addWhereOpr('user_mail', $Login->get('mail'));
                $SQL->addWhereOpr('user_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
                $isSend = true;
            } catch (Exception $e) {
                throw $e;
            }
        }
        return $isSend;
    }
}
