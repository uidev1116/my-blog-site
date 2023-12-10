<?php

namespace Acms\Services\SocialLogin;

use Facebook\Facebook as FacebookSDK;
use Facebook\Exceptions\FacebookSDKException;

class Facebook
{
    /**
     * @var \Facebook\Facebook
     */
    protected $sdk;

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $service;

    /**
     * Facebook constructor.
     * @param string $appId
     * @param string $appSecret
     * @param string $version
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function __construct($appId, $appSecret, $version, $service = 'facebook')
    {
        $this->appId = $appId;
        $this->service = $service;
        if (!$appId && !$appSecret) {
            return;
        }
        $this->sdk = new FacebookSDK([
            'app_id' => $appId,
            'app_secret' => $appSecret,
            'default_graph_version' => $version,
        ]);
    }

    /**
     * 認証URLを取得
     *
     * @param array $permissions
     * @return string
     */
    public function getAuthUrl(array $permissions = ['email']): string
    {
        $helper = $this->sdk->getRedirectLoginHelper();
        return $helper->getLoginUrl($this->getRedirectUrl(), $permissions);
    }

    /**
     * アクセストークンを取得
     *
     * @return string
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        $helper = $this->sdk->getRedirectLoginHelper();
        $accessToken = $helper->getAccessToken();
        if (!isset($accessToken)) {
            if ($helper->getError()) {
                throw new \RuntimeException($helper->getError());
            }
            throw new \RuntimeException('Bad request.');
        }
        $oAuth2Client = $this->sdk->getOAuth2Client();

        $tokenMetadata = $oAuth2Client->debugToken($accessToken);
        $tokenMetadata->validateAppId($this->appId);
        $tokenMetadata->validateExpiration();

        if (!$accessToken->isLongLived()) {
            $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
        }
        $this->accessToken = $accessToken->getValue();
        return $this->accessToken;
    }

    /**
     * ユーザー情報を取得
     *
     * @param string $accessToken
     * @return array
     * @throws FacebookSDKException
     */
    public function getFacebookAccount(string $accessToken): array
    {
        $response = $this->sdk->get('/me?fields=id,name,email,picture', $accessToken);
        $user = $response->getGraphUser();

        $data = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'picture' => '',
        ];
        if ($picture = $user->getPicture()) {
            $data['picture'] = $picture->getUrl();
        }
        return $data;
    }

    /**
     * Facebook OAuth認証のコールバック
     *
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        return acmsLink([
            'protocol' => SSL_ENABLE ? 'https' : 'http',
            'bid' => BID,
        ], false) . 'callback/signin/facebook.html';
    }
}
