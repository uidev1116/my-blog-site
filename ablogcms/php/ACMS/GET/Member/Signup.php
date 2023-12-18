<?php

use Acms\Services\Login\Exceptions\BadRequestException;
use Acms\Services\Login\Exceptions\ExpiredException;
use Acms\Services\Login\Exceptions\NotFoundException;
use Acms\Services\Facades\Login;

class ACMS_GET_Member_Signup extends ACMS_GET_Member
{
    use Acms\Services\Login\Traits\ValidateAuthUrl;

    /**
     * トークンのキーを取得
     *
     * @param array $data
     * @return string
     */
    protected function getTokenKey(array $data): string
    {
        if (!isset($data['uid']) || empty($data['uid'])) {
            return '';
        }
        return $data['uid'];
    }

    /**
     * トークンのタイプを取得
     *
     * @return string
     */
    protected function getTokenType(): string
    {
        return 'signup-confirmation';
    }

    /**
     * 初期処理
     *
     * @return void
     */
    protected function init(): void
    {
        parent::init();

        if ('on' !== config('subscribe')) {
            page404();
        }
    }

    /**
     * テンプレート組み立て
     *
     * @param Template $tpl
     * @return void
     */
    protected function buildTpl(Template $tpl): void
    {
        $vars = [];
        $data = [];
        $step = 'input';

        if ($this->isAuthUrl()) {
            // メールアドレス認証画面
            $step = 'auth';
            $block = 'step#' . $step;
            try {
                $data = $this->validateAuthUrl();
                $uid = $this->findAccount($data);
                Login::subscriberActivation($uid);
                generateSession($uid);

                $this->removeToken(); // 使用済みトークンを削除

                $tpl->add('enableAccount');

                AcmsLogger::info('メールアドレス認証によって、アカウントを有効化しました', [
                    'uid' => $uid,
                    'email' => ACMS_RAM::userMail($uid),
                ]);

                Webhook::call(BID, 'user', ['user:updated'], $uid);
            } catch (BadRequestException $e) {
                AcmsLogger::notice('不正なURLのため、メールアドレス認証によるアカウント有効化に失敗しました', Common::exceptionArray($e, $data));
                $tpl->add('badRequest');
            } catch (ExpiredException $e) {
                AcmsLogger::notice('期限切れのURLのため、メールアドレス認証によるアカウント有効化に失敗しました', Common::exceptionArray($e, $data));
                $tpl->add('expired');
            } catch (NotFoundException $e) {
                AcmsLogger::notice('アカウントが存在しないため、メールアドレス認証によるアカウント有効化に失敗しました', Common::exceptionArray($e, $data));
                $tpl->add('notFound');
            }
        } else {
            // 会員登録画面
            if ($this->Post->isValidAll()) {
                $step = $this->Post->get('step', $step);
            }
            if ($step === 'input') {
                if ($message = config('password_validator_message')) {
                    $vars['passwordPolicyMessage'] = $message;
                }
            }
            $block = 'step#' . $step;
            $vars += $this->buildField($this->Post, $tpl, $block, '');
            $vars['email_auth_signin'] = config('email-auth-signin') === 'on' ? 'on' : 'off';
        }
        $tpl->add($block, $vars);
    }

    /**
     * 対象アカウントの検索
     *
     * @param array $data
     * @return int
     * @throws NotFoundException
     */
    protected function findAccount(array $data): int
    {
        if (!isset($data['uid']) || empty($data['uid'])) {
            throw new BadRequestException('Bad request.');
        }
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereOpr('user_id', $data['uid']);
        $uid = intval(DB::query($sql->get(dsn()), 'one'));
        if (empty($uid)) {
            throw new NotFoundException('Not found account.');
        }
        return $uid;
    }
}
