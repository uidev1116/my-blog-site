<?php

namespace Acms\Services\SocialLogin;

use ACMS_Session;

class Line extends Base
{
    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string
     */
    protected $appSecret;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * CSRF用
     *
     * @var string
     */
    protected $state;

    /**
     * @var array
     */
    protected $loginUrlParam;

    /**
     * @var object
     */
    protected $me;

    /**
     * Facebook constructor.
     * @param string $appId
     * @param string $appSecret
     */
    public function __construct($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;

        $session = ACMS_Session::singleton();
        if ($state = $session->get('line_login_state')) {
            $this->state = $state;
        } else {
            $this->state = uniqueString();
            $session->set('line_login_state', $this->state);
            $session->save();
        }

        $this->loginUrlParam = array(
            'response_type' => 'code',
            'client_id' => $this->appId,
            'redirect_uri' => $this->getRedirectUrl(),
            'state' => $this->state,
            'scope' => 'profile',
        );
    }

    /**
     * Get login url.
     *
     * @return string
     */
    public function getLoginUrl()
    {
        $query = array();
        foreach ($this->loginUrlParam as $key => $val) {
            $query[] = "{$key}={$val}";
        }
        return 'https://access.line.me/oauth2/v2.1/authorize?' . implode('&', $query);
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $code
     *
     * @return string
     */
    public function getAccessToken($code)
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        if (empty($code)) {
            throw new \RuntimeException('Empty code.');
        }
        $postData = array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->getRedirectUrl(),
            'client_id'     => $this->appId,
            'client_secret' => $this->appSecret,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/oauth2/v2.1/token');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response);
        if (!property_exists($json, 'access_token')) {
            throw new \RuntimeException('Failed to get access token.');
        }
        $this->accessToken = $json->access_token;

        return $this->accessToken;
    }

    /**
     * ユーザー情報を取得
     *
     * @param string $accessToken
     */
    public function setMe($accessToken)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
        curl_setopt($ch, CURLOPT_URL, 'https://api.line.me/v2/profile');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($response);
        if (!property_exists($json, 'userId')) {
            throw new \RuntimeException('Failed to get profile.');
        }
        $this->me = $json;
    }

    /**
     * @return string
     */
    protected function getRedirectUrl()
    {
        return acmsLink(array('bid'=>BID, '_protocol'=>'http'), false) . 'callback/signin/line.html';
    }

    /**
     * @return mixed
     */
    protected function getId()
    {
        return $this->me->userId;
    }

    /**
     * @return mixed
     */
    protected function getEmail()
    {
        return $this->getId() . '@example.com';
    }

    /**
     * @return mixed
     */
    protected function getName()
    {
        return $this->me->displayName;
    }

    /**
     * @return mixed
     */
    protected function getCode()
    {
        return $this->getId();
    }

    /**
     * @return mixed
     */
    protected function getIcon()
    {
        if (property_exists($this->me, 'pictureUrl')) {
            return $this->userIconFromUri($this->me->pictureUrl);
        }
        return '';
    }

    /**
     * @return mixed
     */
    protected function getUserKey()
    {
        return 'user_line_id';
    }
}
