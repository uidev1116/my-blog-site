<?php

class ACMS_POST_Member_ResetPasswordAuth extends ACMS_POST_Member
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
     * Run
     *
     * @return Field_Validation
     */
    public function post(): Field_Validation
    {
        $user = $this->extract('user');
        $login = $this->extract('login');
        $data = $this->validateAuthUrl();
        $uid = $this->findUser($data);
        $user->setMethod('reset', 'isOperable', !SUID && $uid > 0);
        $this->validate($user);

        if ($this->Post->isValidAll()) {
            $this->updatePassword($uid, $user->get('pass'));
            AcmsLogger::info('パスワードの再設定を行いました', [
                'uid' => $uid,
                'name' => ACMS_RAM::userName($uid),
            ]);
            if (Tfa::isAvailableAccount($uid)) {
                $login->set('reset', 'success');
                $login->set('tfa', 'on');
                return $this->Post;
            }
            // ログイン日時更新
            $sql = SQL::newUpdate('user');
            $sql->addUpdate('user_pass_reset', '');
            $sql->addUpdate('user_login_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            $sql->addWhereOpr('user_id', $uid);
            DB::query($sql->get(dsn()), 'exec');

            generateSession($uid); // セッション生成
            $login->set('reset', 'success');

        } else {
            if (!$user->isValid('pass', 'required')) {
                AcmsLogger::info('パスワードが指定されていないため、パスワード再設定処理を中断しました', [
                    'uid' => $uid,
                    'email' => ACMS_RAM::userMail($uid),
                ]);
            }
            if (!$user->isValid('pass', 'password')) {
                AcmsLogger::info('不正なパスワード形式のため、パスワード再設定処理を中断しました', [
                    'uid' => $uid,
                    'email' => ACMS_RAM::userMail($uid),
                ]);
            }
            if (!$user->isValid('retype_pass', 'equalTo')) {
                AcmsLogger::info('パスワード入力が一致しないため、パスワード再設定処理を中断しました', [
                    'uid' => $uid,
                    'email' => ACMS_RAM::userMail($uid),
                ]);
            }
            if (!$user->isValid('reset', 'isOperable')) {
                if (empty($context)) {
                    AcmsLogger::notice('不正なURLまたは有効期限切れのURLのため、パスワード再設定処理を中断しました');
                } else {
                    AcmsLogger::notice('不正なURLまたは操作のため、パスワード再設定処理を中断しました', $context);
                }
            }
        }
        return $this->Post;
    }

    /**
     * バリデーション
     *
     * @param Field_Validation $user
     * @return void
     */
    protected function validate(Field_Validation $user): void
    {
        $user->setMethod('pass', 'required');
        $user->setMethod('pass', 'password');
        $user->setMethod('retype_pass', 'equalTo', 'pass');
        $user->validate(new ACMS_Validator());
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
     * 認証URLからユーザーを特定
     *
     * @param array $context
     * @return int
     */
    protected function findUser(array $context): int
    {
        if (!isset($context['email']) || !isset($context['token'])) {
            return -1;
        }
        $sql = SQL::newSelect('user');
        $sql->addWhereOpr('user_status', 'open');
        $sql->addWhereOpr('user_mail', $context['email']);
        $sql->addWhereOpr('user_blog_id', BID);
        $sql->addWhereIn('user_auth', $this->limitedAuthority());
        $row = DB::query($sql->get(dsn()), 'row');
        if (empty($row)) {
            return -1;
        }
        return intval($row['user_id']);
    }

    /**
     * パスワードを更新
     *
     * @param int $uid
     * @param string $newPassword
     * @return void
     */
    protected function updatePassword(int $uid, string $newPassword): void
    {
        $sql = SQL::newUpdate('user');
        $sql->addUpdate('user_pass', acmsUserPasswordHash($newPassword));
        $sql->addUpdate('user_pass_generation', PASSWORD_ALGORITHM_GENERATION);
        $sql->addWhereOpr('user_id', $uid);
        DB::query($sql->get(dsn()), 'exec');
        ACMS_RAM::user($uid, null);

        $this->removeToken(); // 使用済みのトークンを削除
    }
}
