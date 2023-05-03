<?php

namespace Acms\Services\SocialLogin;

use CristianPontes\ZohoCRMClient\Exception\Exception;
use Facebook\Facebook as FacebookSDK;
use Facebook\Authentication\AccessToken;
use DB;
use SQL;

class Facebook extends Base
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
     * @var \Facebook\GraphNodes\GraphUser
     */
    protected $me;

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
        $this->sdk = new FacebookSDK(array(
            'app_id' => $appId,
            'app_secret' => $appSecret,
            'default_graph_version' => $version,
        ));
    }

    /**
     * Get login url.
     *
     * @param string $url
     * @param array $permissions
     * @return string
     */
    public function getLoginUrl($url, $permissions = array('email'))
    {
        $helper = $this->sdk->getRedirectLoginHelper();
        return $helper->getLoginUrl($url, $permissions);
    }

    /**
     * @return string
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getAccessToken()
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
     * insert oauth token
     *
     *
     * @param string $bid
     * @param string $token
     * @param string $secret
     * @param string $type = 'access' | 'request'
     * @param string $service = 'facebook'
     * @return string
     */

    public function insertOAuthToken($bid, $token)
    {
        $service = $this->service;
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newInsert('config');
        $SQL->addInsert('config_key', "{$service}_graph_oauth_access_token");
        $SQL->addInsert('config_value', $token);
        $SQL->addInsert('config_blog_id', $bid);
        if ( !$DB->query($SQL->get(dsn()), 'exec') ) {
            return false;
        }
        return true;
    }

    /**
     * load oauth token
     *
     * @param string $bid
     * @return string
     */
    public function loadAccessToken($bid)
    {
        $DB     = DB::singleton(dsn());
        $service = $this->service;
        $target = "{$service}_graph_oauth_access_token";

        $SQL    = SQL::newSelect('config');
        $SQL->addSelect('config_key');
        $SQL->addSelect('config_value');
        $SQL->addWhereOpr('config_key', $target);
        $SQL->addWhereOpr('config_blog_id', $bid);
        $row    = $DB->query($SQL->get(dsn()), 'row');

        if ( empty($row) ) {
            return false;
        }

        $this->accessToken = $row['config_value'];
        $accessToken = new AccessToken($this->accessToken);
        $oAuth2Client = $this->sdk->getOAuth2Client();
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);
        $tokenMetadata->validateAppId($this->appId);
        $tokenMetadata->validateExpiration();
        if ($accessToken->isExpired()) {
            $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            $this->accessToken = $accessToken->getValue();
            $this->insertOAuthToken($bid, $this->accessToken);
        }
        return $this->accessToken;
    }
    /**
     * removeAccessToken
     *
     * @param string $bid
     * @param string $type = 'access' | 'request'
     * @param string $service = 'facebook'
     * @return string
    */
    public function removeAccessToken($bid)
    {
        $DB     = DB::singleton(dsn());
        $service = $this->service;
        $target = "{$service}_graph_oauth_access_token";
        $SQL    = SQL::newDelete('config');
        $SQL->addWhereOpr('config_key', $target);
        $SQL->addWhereOpr('config_blog_id', $bid);
        $DB->query($SQL->get(dsn()), 'exec');
    }

    /**
     * set accesstoken
     */
    public function setAccessToken($token)
    {
        $this->accessToken  = $token;
    }

    public function setMe()
    {
        $accessToken = $this->getAccessToken();
        $response = $this->sdk->get('/me?fields=id,name,email', $accessToken);
        $this->me = $response->getGraphUser();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->me->getId();
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        $email = $this->me->getEmail();
        if (empty($email)) {
            $email = $this->getId() . '@example.com';
        }
        return $email;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->me->getName();
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->me->getId();
    }
    /**
     * @return mixed
     */
    public function getPicture()
    {
        return $this->me->getPicture();
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        $image_uri = 'https://graph.facebook.com/' . $this->getId() . '/picture?type=large';
        return $this->userIconFromUri($image_uri);
    }

    public function getBussinessAccounts()
    {
        $accessToken = $this->getAccessToken();
        $response = $this->sdk->get("/me?fields=accounts{instagram_business_account,name}", $accessToken);
        $items = $response->getGraphObject()->uncastItems();
        $accounts = array();
        if (!isset($items['accounts'])) {
            return $accounts;
        }
        foreach ($items['accounts'] as $item) {
            if (isset($item['instagram_business_account'])) {
                $accounts[] = array(
                    "id" => $item['instagram_business_account']['id'],
                    "name" => $item['name']
                );
            }
        }
        return $accounts;
    }

    public function getMedia($config)
    {
        $media = array();
        $pager = array();
        $accessToken = $this->getAccessToken();
        $nextPageId = $config['nextPageId'];
        $accountId = $config['businessAccount'];
        $limit = $config['limit'];
        $searchField = "like_count,comments_count,caption,media_type,media_url,permalink,thumbnail_url,username,owner,timestamp";
        if ($nextPageId) {
            $searchField .= "&after=$nextPageId";
        }

        if ($limit) {
            $searchField .= "&limit=$limit";
        }

        if ($accountId) {
            $response = $this->sdk->get("/${accountId}/media?fields=${searchField}", $accessToken);
            $items = $response->getGraphEdge();
            $body = $response->getDecodedBody();
            $pager = $body['paging'];
            foreach ($items as $graphNode) {
                $media[] = $graphNode->uncastItems();
            }
        }
        return array(
            'media' => $media,
            'pager' => $pager,
            'userIcon' => $this->getIcon()
        );
    }

    /**
     * @return mixed
     */
    public function getUserKey()
    {
        return 'user_facebook_id';
    }
}
