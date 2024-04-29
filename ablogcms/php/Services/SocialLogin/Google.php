<?php

namespace Acms\Services\SocialLogin;

use Google\Client;

class Google
{
    /**
     * Client ID
     *
     * @var string
     */
    protected $clientId;

    /**
     * Secret Key
     *
     * @var string
     */
    protected $secretKey;

    /**
     * API Clinent
     * @var \Google\Client
     */
    protected $client;

    /**
     * Constructor
     *
     * @param string $clientId
     * @param string $secretKey
     * @return void
     */
    public function __construct(string $clientId, string $secretKey)
    {
        $this->clientId = $clientId;
        $this->secretKey = $secretKey;
        $this->client = $this->getClient();
    }

    /**
     * APIが使用できるか判断
     *
     * @return bool
     */
    public function isEnable(): bool
    {
        return $this->clientId && $this->secretKey;
    }

    /**
     * 認証URLを取得
     *
     * @return string
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * コードからアクセストークンを取得
     *
     * @param string $code
     * @return array
     */
    public function getAccessToken(string $code): array
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    /**
     * アクセストークンの検証・ユーザー情報の取得
     * @param array $accessToken
     * @return array
     */
    public function verifyIdToken(array $accessToken): array
    {
        if (!isset($accessToken['id_token']) || empty($accessToken['id_token'])) {
            throw new \RuntimeException('トークンがありません');
        }
        $userInfo = $this->client->verifyIdToken($accessToken['id_token']);
        if (!$userInfo) {
            throw new \RuntimeException('トークンの検証に失敗しました');
        }
        if (!isset($userInfo['sub'])) {
            throw new \RuntimeException('トークンの検証に失敗しました');
        }
        return [
            'sub' => $userInfo['sub'],
            'email' => $userInfo['email'],
            'name' => $userInfo['name'],
            'picture' => $userInfo['picture'],
        ];
    }

    /**
     * Google APIアクセスのクライアントを取得
     *
     * @return null|\Google\Client
     */
    protected function getClient(): ?Client
    {
        if (!$this->isEnable()) {
            return null;
        }
        $redirectUri = acmsLink(['bid' => BID], false) . 'callback/signin/google.html';
        $client = new Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->secretKey);
        $client->setRedirectUri($redirectUri);
        $client->addScope("email");
        $client->addScope("profile");

        return $client;
    }
}
