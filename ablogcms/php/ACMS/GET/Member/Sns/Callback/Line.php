<?php

use Acms\Services\Facades\Login;

class ACMS_GET_Member_Sns_Callback_Line extends ACMS_GET
{
    use Acms\Services\Login\Traits\SnsAuthCallback;

    /**
     * Main
     *
     * @return void
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
        return 'LINE';
    }

    /**
     * データベースにSNSのsubを登録するカラム名
     *
     * @return string
     */
    protected function getKeyName(): string
    {
        return 'user_line_id';
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
        if (!isset($data['userId']) || empty($data['userId'])) {
            throw new \RuntimeException('LineアカウントのID取得に失敗しました');
        }
        return $data['userId'];
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
        if (!isset($data['displayName']) || empty($data['displayName'])) {
            throw new \RuntimeException('Lineアカウント名の取得に失敗しました');
        }
        return $data['displayName'];
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
        if (!isset($data['userId']) || empty($data['userId'])) {
            throw new \RuntimeException('Lineアカウントのメールアドレスの取得に失敗しました');
        }
        return $data['userId'] . '@example.com';
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
        if (!isset($data['pictureUrl']) || empty($data['pictureUrl'])) {
            return '';
        }
        return Login::userIconFromUri($data['pictureUrl']);
    }

    /**
     * 認証してユーザー情報を取得
     *
     * @return array
     * @throws RuntimeException
     */
    protected function oauth(): array
    {
        $code = $this->Get->get('code');
        if (empty($code)) {
            throw new RuntimeException('Empty code.');
        }
        $lineApi = App::make('line-login');
        $accessToken = $lineApi->getAccessToken($code);
        if ($accessToken) {
            return $lineApi->getLineAccount($accessToken);
        }
        throw new RuntimeException('Bad request.');
    }
}
