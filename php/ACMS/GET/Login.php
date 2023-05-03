<?php

use Acms\Services\Login\Exceptions\BadRequestException;
use Acms\Services\Login\Exceptions\ExpiredException;
use Acms\Services\Login\Exceptions\NotFoundException;

class ACMS_GET_Login extends ACMS_GET
{
    public function get()
    {
        if (SUID) {
            page404();
        }
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $block = ALT ? ALT : 'auth';
        $Login =& $this->Post->getChild('login');
        $type = $this->Get->get('type');

        if (!defined('IS_LOGIN_PAGE')) {
            define('IS_LOGIN_PAGE', true);
        }

        /**
         * two factor auth
         */
        if ($Login->get('tfa') === 'on') {
            $block = 'tfa';
        }
        if ($Login->get('tfaRecovery') === 'on') {
            $block = 'auth';
        }

        /**
         * subscribe auth.
         */
        if ('on' == config('subscribe') && $type === 'subscribe') {
            try {
                $uid = $this->validateAuthUrl();
                $this->subscriberActivation($uid);
                $Tpl->add(array('subscribe.enableAccount', $block));
            } catch (BadRequestException $e) {
                $Tpl->add(array('subscribe.badRequest', $block));
            } catch (ExpiredException $e) {
                $Tpl->add(array('subscribe.expired', $block));
            } catch (NotFoundException $e) {
                $Tpl->add(array('subscribe.notFound', $block));
            }
        }

        /**
         * reset password
         */
        if ($type === 'reset' && $Login->get('reset') !== 'success') {
            try {
                $uid = $this->validateResetUrl();
                // メールアドレスが未認証のユーザーはパスワードリセットメールを送信できないため、
                // 通常ではメールアドレス未認証のユーザーがリセットメールの認証を通過することはない。
                // しかし、万が一、メールアドレス未認証のユーザーが
                // パスワードリセットしてしまうと、本来はログインできないユーザーが
                // ログインできてしまう他、そのユーザーは一定期間で削除されてしまうという
                // 意図しない挙動になってしまうため、ユーザーの有効化を行う
                $this->subscriberActivation($uid);
                $block = 'reset';
            } catch (BadRequestException $e) {
                $Tpl->add(array('reset.badRequest', $block));
            } catch (ExpiredException $e) {
                $Tpl->add(array('reset.expired', $block));
            } catch (NotFoundException $e) {
                $Tpl->add(array('reset.notFound', $block));
            }
        } elseif ($Login->get('reset') === 'success') {
            // 2段階認証が利用可能なユーザーはパスワードリセット時にログインしないため、
            // パスワードリセットが成功したことを表示するためのブロックを追加する。
            $Tpl->add(array('reset.success', $block));
        }

        $vars = array(
            'trialTime' => config('login_trial_time', 5),
            'trialNumber' => config('login_trial_number', 5),
            'lockTime' => config('login_lock_time', 5),
        );
        if ($message = config('password_validator_message')) {
            $vars['passwordPolicyMessage'] = $message;
        }

        if ($this->Post->isNull()) {
            $Tpl->add(array('sendMsg#before', $block));
            $Tpl->add(array('submit', $block));
            $vars += array(
                'mail' => $this->Get->get('reset', $this->Get->get('subscribe')),
                'step' => 'step',
            );
        } else {
            $systemError = $this->Post->getChild('systemErrors');
            if ($this->Post->isValidAll() && $systemError->isNull()) {
                $Tpl->add(array('sendMsg#after', $block));
                $vars += array(
                    'step' => 'result',
                );
            } else {
                $Tpl->add(array('submit', $block));
                $vars += array(
                    'step' => 'reapply',
                );
            }
        }
        $vars += $this->buildField($this->Post, $Tpl, $block, 'login');

        //------------
        // if expired
        if (!IS_LICENSED && $block == 'auth') {
            $Tpl->add(array('expired', $block));
        }

        //-----------
        // blog index
        if ($Login->get('loginIndex') == 'yes') {
            $block = 'select';
            $bidAry = $Login->getArray('bid');
            foreach ($bidAry as $bid) {
                $Tpl->add(array('selectBlog:loop', $block), array(
                    'bid' => $bid,
                    'name' => ACMS_RAM::blogName($bid),
                    'url' => acmsLink(array(
                        'bid' => $bid,
                        'sid' => $Login->get('sid'),
                    ), false),
                ));
            }
            //-----------
            // subscribe
        } else {
            if ('on' == config('subscribe')) {
                $Tpl->add(array('subscribeLink', $block));
            } else {
                if ('subscribe' == ALT) {
                    $block = 'auth';
                }
            }
        }

        $Tpl->add($block, $vars);

        return $Tpl->get();
    }

    /**
     * @return int
     * @throws BadRequestException
     * @throws NotFoundException
     */
    protected function validateResetUrl()
    {
        $key = $this->Get->get('key');
        $salt = $this->Get->get('salt');
        $context = $this->Get->get('context');

        $context = Login::validateTimedLinkParams($key, $salt, $context);
        if (!isset($context['email']) || !isset($context['token'])) {
            throw new BadRequestException('Bad request.');
        }
        // find account
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereOpr('user_mail', $context['email']);
        $sql->addWhereOpr('user_reset_password_token', $context['token']);
        $uid = intval(DB::query($sql->get(dsn()), 'one'));
        if (empty($uid)) {
            throw new NotFoundException('Not found account.');
        }
        return $uid;
    }

    /**
     * @return int
     * @throws BadRequestException
     * @throws NotFoundException
     */
    protected function validateAuthUrl()
    {
        $key = $this->Get->get('key');
        $salt = $this->Get->get('salt');
        $context = $this->Get->get('context');

        $context = Login::validateTimedLinkParams($key, $salt, $context);
        if (!isset($context['email']) || !isset($context['token'])) {
            throw new BadRequestException('Bad request.');
        }
        // find account
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereOpr('user_mail', $context['email']);
        $sql->addWhereOpr('user_confirmation_token', $context['token']);
        $uid = intval(DB::query($sql->get(dsn()), 'one'));
        if (empty($uid)) {
            throw new NotFoundException('Not found account.');
        }
        return $uid;
    }

    /**
     * @param int $uid
     * @return bool
     */
    protected function subscriberActivation($uid)
    {
        // enable account
        $sql = SQL::newUpdate('user');
        $sql->addUpdate('user_confirmation_token', '');
        $sql->addWhereOpr('user_id', $uid);
        DB::query($sql->get(dsn()), 'exec');
        ACMS_RAM::user($uid, null);

        return true;
    }
}
