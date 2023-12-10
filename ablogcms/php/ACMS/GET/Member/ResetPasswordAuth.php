<?php

use Acms\Services\Login\Exceptions\BadRequestException;
use Acms\Services\Login\Exceptions\ExpiredException;
use Acms\Services\Login\Exceptions\NotFoundException;

class ACMS_GET_Member_ResetPasswordAuth extends ACMS_GET_Member
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
        if (!isset($data['email']) || empty($data['email'])) {
            return '';
        }
        return BID . '_' . $data['email'];
    }

    /**
     * トークンのタイプを取得
     *
     * @return string
     */
    protected function getTokenType(): string
    {
        return 'reset-password';
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
        $login = $this->Post->getChild('login');

        if ($login->get('reset') === 'success') {
            if ($login->get('tfa') === 'on') {
                $tpl->add(['tfa-on', 'success']);
            } else {
                $tpl->add(['tfa-off', 'success']);
            }
            $tpl->add('success');

        } else {
            try {
                $data = $this->validateAuthUrl();
                $this->findAccount($data);
                if ($message = config('password_validator_message')) {
                    $vars['passwordPolicyMessage'] = $message;
                }
                $vars += $this->buildField($this->Post, $tpl);
                $tpl->add('form', $vars);
            } catch (BadRequestException $e) {
                AcmsLogger::notice('不正なURLのため、パスワード再設定処理を中断しました', Common::exceptionArray($e, $data));
                $tpl->add('badRequest');
            } catch (ExpiredException $e) {
                AcmsLogger::notice('有効期限切れのURLのため、パスワード再設定処理を中断しました', Common::exceptionArray($e, $data));
                $tpl->add('expired');
            } catch (NotFoundException $e) {
                AcmsLogger::notice('アカウントが存在しないため、パスワード再設定処理を中断しました', Common::exceptionArray($e, $data));
                $tpl->add('notFound');
            }
            $tpl->add('notSuccessful');
        }
        $tpl->add(null, $vars);
    }

    /**
     * 権限の限定
     *
     * @return array
     */
    protected function limitedAuthority(): array
    {
        return Login::getSinginAuth();
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
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereOpr('user_status', 'open');
        $sql->addWhereOpr('user_mail', $data['email']);
        $sql->addWhereIn('user_auth', $this->limitedAuthority());
        $sql->addWhereOpr('user_blog_id', BID);
        $uid = intval(DB::query($sql->get(dsn()), 'one'));
        if (empty($uid)) {
            throw new NotFoundException('Not found account.');
        }
        return $uid;
    }
}
