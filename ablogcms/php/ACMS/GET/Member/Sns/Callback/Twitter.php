<?php

use Acms\Services\Facades\Login;

class ACMS_GET_Member_Sns_Callback_Twitter extends ACMS_GET
{
    use Acms\Services\Login\Traits\SnsAuthCallback;

    /**
     * Main
     *
     * @return never
     */
    public function get()
    {
        $this->oAuthCallbackProcess();
    }

    /**
     * SNSサービス名
     *
     * @return string
     */
    protected function getServiceName(): string
    {
        return 'Twitter';
    }

    /**
     * データベースにSNSのsubを登録するカラム名
     *
     * @return string
     */
    protected function getKeyName(): string
    {
        return 'user_twitter_id';
    }

    /**
     * APIレスポンスから、アカウント識別IDを取得
     *
     * @param array $data
     * @return string
     * @throws RuntimeException
     */
    protected function getSubId(array $data): string
    {
        if (!isset($data['sub']) || empty($data['sub'])) {
            throw new \RuntimeException('TwitterアカウントのID取得に失敗しました');
        }
        return $data['sub'];
    }

    /**
     * APIレスポンスから、アカウント名を取得
     *
     * @param array $data
     * @return string
     * @throws RuntimeException
     */
    protected function getUserName(array $data): string
    {
        if (!isset($data['name']) || empty($data['name'])) {
            throw new \RuntimeException('Twitterアカウント名の取得に失敗しました');
        }
        return $data['name'];
    }

    /**
     * APIレスポンスから、Emailアドレスを取得
     *
     * @param array $data
     * @return string
     * @throws RuntimeException
     */
    protected function getEmail(array $data): string
    {
        if (!isset($data['email']) || empty($data['email'])) {
            return $data['sub'] . '@example.com';
        }
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereOpr('user_mail', $data['email']);
        if (DB::query($sql->get(dsn()), 'one')) {
            return $data['sub'] . '@example.com';
        }
        return $data['email'];
    }

    /**
     * APIレスポンスから、アカウントアイコンを取得
     *
     * @param array $data
     * @return string
     * @throws RuntimeException
     */
    protected function getIcon(array $data): string
    {
        if (!isset($data['picture']) || empty($data['picture'])) {
            return '';
        }
        return Login::userIconFromUri($data['picture']);
    }

    /**
     * 認証してユーザー情報を取得
     *
     * @return array
     * @throws RuntimeException
     */
    protected function oauth(): array
    {
        $token = $this->Get->get('oauth_token');
        $verifier = $this->Get->get('oauth_verifier');
        $twitterApi = App::make('twitter-login');
        $tokenCredentials = $twitterApi->getTokenCredentials($token, $verifier);
        if ($tokenCredentials) {
            return $twitterApi->getTwitterAccount($tokenCredentials);
        }
        throw new RuntimeException('Bad request.');
    }
}
