<?php

use Acms\Services\Facades\Common;
use Acms\Services\Login\Exceptions\BadRequestException;
use Acms\Services\Login\Exceptions\ExpiredException;

class ACMS_POST_Member_Admin_LoginWithVerifyCode extends ACMS_POST_Member_Admin_LoginWithEmail
{
    /**
     * Run
     *
     * @return null|Field_Validation
     */
    public function post(): ?Field_Validation
    {
        $loginField = $this->extract('login');
        $email = preg_replace("/(\s|　)/", "", $loginField->get('mail'));
        $code = $loginField->get('code');
        $lockKey = md5('LoginWithEmail' . $email);
        $data = [];

        $loginField->deleteField('sent');

        // ユーザー決定前のバリデート
        $this->preValidate($loginField, $email, $lockKey);
        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }

        if (!$this->varifyCode($email, $code)) {
            $loginField->setMethod('code', 'auth', false);
            $loginField->validate(new ACMS_Validator());
            Common::logLockPost($lockKey);
            return $this->Post;
        }

        try {
            $all = $this->find($email, '');

            // ユーザーが見つからない or 複数見つかった
            if (empty($all) || 1 < count($all)) {
                Common::logLockPost($lockKey);
                throw new BadRequestException('アカウントが存在しないため、不正なリクエストと判断しました');
            }

            // 一意のユーザー
            $user = $all[0];
            $uid = intval($user['user_id']);

            // DB更新
            $sql = SQL::newUpdate('user');
            $sql->addUpdate('user_login_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
            $sql->addWhereOpr('user_id', $uid);
            DB::query($sql->get(dsn()), 'exec');

            // セッション生成
            generateSession($uid);
            $this->removeVerifyCode($email);

            AcmsLogger::info('ユーザー「' . ACMS_RAM::userName($uid). '」が管理ログインしました', [
                'id' => $uid,
            ]);

            Webhook::call(BID, 'user', ['user:login'], $uid);

            // リダイレクト処理
            Login::loginRedirect(ACMS_RAM::user($uid), '');

        } catch (BadRequestException $e) {
            $loginField->setMethod('code', 'request', false);
            AcmsLogger::notice('不正な確認コードのため、確認コードによる管理ログインに失敗しました', Common::exceptionArray($e, $data));

        } catch (ExpiredException $e) {
            $loginField->setMethod('code', 'expired', false);
            AcmsLogger::notice('有効期限切れの確認コードのため、確認コードによる管理ログインに失敗しました', Common::exceptionArray($e, $data));
        }
        $loginField->validate(new ACMS_Validator());

        return $this->Post;
    }
}
