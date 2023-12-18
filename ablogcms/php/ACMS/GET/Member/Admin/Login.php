<?php

use Acms\Services\Login\Exceptions\BadRequestException;
use Acms\Services\Login\Exceptions\ExpiredException;

class ACMS_GET_Member_Admin_Login extends ACMS_GET_Member_Signin
{
    /**
     * トークンのタイプを取得
     *
     * @return string
     */
    protected function getTokenType(): string
    {
        return 'email-login';
    }

    /**
     * メール認証によるサインイン
     *
     * @param Template $tpl
     * @return void
     */
    protected function emailAuthSingin(Template $tpl): void
    {
        $data = [];

        // メールアドレス認証画面
        try {
            $data = $this->validateAuthUrl();
            if (!isset($data['uid'])) {
                throw new BadRequestException('uid情報がないため、不正なリクエストと判断しました');
            }
            $uid = intval($data['uid']);

            // DB更新
            $sql = SQL::newUpdate('user');
            $sql->addUpdate('user_pass_reset', '');
            $sql->addUpdate('user_login_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            $sql->addWhereOpr('user_id', $uid);
            DB::query($sql->get(dsn()), 'exec');

            // セッション生成
            generateSession($uid);
            $this->removeToken();

            AcmsLogger::info('ユーザー「' . ACMS_RAM::userName($uid). '」が管理ログインしました', [
                'id' => $uid,
            ]);

            Webhook::call(BID, 'user', ['user:login'], $uid);

            // リダイレクト処理
            Login::loginRedirect(ACMS_RAM::user($uid), '');
        } catch (BadRequestException $e) {
            $tpl->add('badRequest');
            AcmsLogger::notice('不正なURLのため、メール認証管理ログインに失敗しました', Common::exceptionArray($e, $data));
        } catch (ExpiredException $e) {
            $tpl->add('expired');
            AcmsLogger::notice('有効期限切れのURLのため、メール認証管理ログインに失敗しました', Common::exceptionArray($e, $data));
        }
    }
}
