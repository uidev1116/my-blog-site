<?php

// 2011-06-06 版

/**
 * OAuthコンシューマー
 *
 * @package     OAuth
 * @copyright   2010 ayumusato.com
 * @license     MIT License
 * @author      Ayumu Sato
 */
abstract class OAuth_Consumer
{
    public $OAuth;
    public $Response;

    protected $request_token_url;
    protected $access_token_url;
    protected $authorize_url;

    /**
     * request, access, authorizeの各urlプロパティをセットする
     *
     * @abstract
     * @return void
     */
    abstract public function setUrl();

    /**
     * OAuthリクエストを発行して，Responseプロパティに結果を格納する
     *
     * @abstract
     * @param string $url
     * @param array $params
     * @param string $http_method
     * @return bool
     */
    abstract public function httpRequest($url, $params = array(), $http_method = 'GET');

    /**
     * 初期化
     *
     * @param string $key
     * @param string $secret
     */
    protected function __construct($key, $secret)
    {
        $this->setUrl();
        $this->OAuth = new OAuth_Client($key, $secret);
    }

    /**
     * レスポンスのクエリをパースして配列に変換する
     *
     * @param  $query
     * @return array|bool
     */
    protected function _parseQuery($query)
    {
        if ( empty($query) ) return false;

        $ary    = explode('&', $query);

        if ( !is_array($ary) ) return false;

        $parsed = array();
        foreach ( $ary as $a ) {
            list($key, $val) = explode('=', $a);
            $parsed[$key]  = $val;
        }

        return !empty($parsed) ? $parsed : false;
    }

    /**
     * トークンが取得できたら，OAuthをRequestToken取得済みのインスタンスに昇格
     *
     * @return array|bool
     */
    public function getReqToken($params=array())
    {
        if ( !!($this->httpRequest($this->request_token_url, $params)) ) {
            $token  = $this->_parseQuery($this->Response->getResponseBody());
            if ( !empty($token) ) {
                $this->OAuth = OAuth_Client::RequestToken($this->OAuth, $token['oauth_token'], $token['oauth_token_secret']);
                return $token;
            }
        }
        return $this->Response->body;
    }

    /**
     * トークンが取得できたら，OAuthをAccessToken取得済みのインスタンスに昇格
     *
     * @return array|bool
     */
    public function getAcsToken($params=array())
    {
        if ( !!($this->httpRequest($this->access_token_url, $params)) ) {
            $token  = $this->_parseQuery($this->Response->getResponseBody());
            if ( !empty($token) ) {
                $this->OAuth = OAuth_Client::AccessToken($this->OAuth, $token['oauth_token'], $token['oauth_token_secret']);
                return $token;
            }
        }
        return $this->Response->body;
    }
}

/**
 * OAuth処理を制御するオブジェクト
 *
 * @package     OAuth
 * @copyright   2010 ayumusato.com
 * @license     MIT License
 * @author      Ayumu Sato
 */
class OAuth_Client
{
    protected $version = '1.0';
    protected $base;

    public $key;
    public $secret;

    public $token;
    public $token_secret;

    public $params = array(
        'oauth_callback'        => null,
        'scope'                 => null,
        'oauth_verifier'        => null,
        'oauth_session_handle'  => null,
        'xoauth_dispalay_name'  => null,
    );

    /**
     * コンストラクタ
     *
     * @param string $key
     * @param string $secret
     * @param string $token
     * @param string $token_secret
     */
    public function __construct($key, $secret, $token = null, $token_secret = null)
    {
        $this->key     = $key;
        $this->secret  = $secret;

        if ( !empty($token) && !empty($token_secret) )
        {
            $this->token        = $token;
            $this->token_secret = $token_secret;
        }
        else
        {
            $this->token        = null;
            $this->token_secret = null;
        }
    }

    /**
     * OAuthオブジェクトを，RequestTokenを保持したOAuthオブジェクトにして返す
     *
     * @param OAuth_Client $consumer
     * @param string $token
     * @param string $token_secret
     * @return OAuth_RequestToken
     */
    public static function RequestToken($consumer, $token, $token_secret)
    {
        return new OAuth_RequestToken($consumer, $token, $token_secret);
    }

    /**
     * OAuthオブジェクトを，AccessTokenを保持したOAuthオブジェクトにして返す
     *
     * @param OAuth_Client $consumer
     * @param string $token
     * @param string $token_secret
     * @return OAuth_AccessToken
     */
    public static function AccessToken($consumer, $token, $token_secret)
    {
        return new OAuth_AccessToken($consumer, $token, $token_secret);
    }

    /**
     * OAuth用のプロバイダ依存な追加パラメータをセットする
     *
     * @param string $key
     * @param string $val
     * @return void
     */
    public function setParam($key, $val)
    {
        $this->params[$key] = $val;
    }

    /**
     * セット済みのOAuth用のパラメータを，マージする
     *
     * @param array $params ( base parameter )
     * @param string $method ( get / post )
     * @return array $params
     */
    public function mergeOAuthParams($params, $method)
    {
        $params = array_merge($params, array(
            'oauth_version'         => $this->version,
            'oauth_nonce'           => md5(microtime().mt_rand()),
            'oauth_timestamp'       => time(),
            'oauth_consumer_key'    => $this->key,
            'oauth_signature_method'=> $method,
        ));

        // その他のパラメーターを，空白を切り詰めてからマージする
        $params = array_merge($params, array_merge(array_diff($this->params, array(''))));

        // トークンを保持していれば含ませる
        if ( !empty($this->token) ) {
            $params['oauth_token'] = $this->token;
        }

        return $params;
    }

    /**
     * APIのベースURL，パラメータ，APIのメソッド，HTTPリクエストメソッドから，
     * OAuthのシグニチャを生成する
     *
     * @param string $url ( base url )
     * @param array  $params
     * @param string $method ( signature method )
     * @param string $http_method
     * @return string
     */
    public function buildSignature($url, $params, $method, $http_method = 'GET')
    {
        $material   = array(
            OAuth_Signature::rfc3986($http_method),
            OAuth_Signature::rfc3986($url),
            OAuth_Signature::rfc3986($this->_httpBuildQuery($params)),
        );

        $this->base = implode('&', $material);

        switch ($method)
        {
            case 'HMAC-SHA1' :
                $signature = OAuth_Signature::hmacSha1($this->base, $this->secret, $this->token_secret);
                return $signature;
            default:
                return '';
                break;
        }
    }

    /**
     * APIのベースURL，パラメータ，APIのメソッド，HTTPリクエストメソッドから，
     * リクエスト用のフルURLを作成
     *
     * @param string $url ( base url )
     * @param array  $params
     * @param string $method ( signature method )
     * @param string $http_method
     * @return string complete url
     */
    public function buildRequest($url, $params, $method, $http_method = 'GET')
    {
        $params = $this->mergeOAuthParams($params, $method);

        $params['oauth_signature'] = $this->buildSignature($url, $params, $method, $http_method);

        $query = $this->_httpBuildQuery($params);

        return $url.'?'.$query;
    }

    /**
     * OAuthを施した，HTTPリクエスト用のGETクエリを作成
     *
     * @param array $params
     * @return string
     */
    public function _httpBuildQuery($params)
    {
        $encoded = array();
        foreach ($params as $key => $val) {
            $key    = OAuth_Signature::rfc3986($key);
            $val    = OAuth_Signature::rfc3986($val);
            $encoded[$key] = $val;
        }
        $params = $encoded;
        ksort($params);

        $queries = array();
        foreach ( $params as $key => $val ) {
            $queries[] = $key.'='.$val;
        }

        return implode('&', $queries);
    }
}

/**
 * RequestTokenを保持している状態のOAuthオブジェクト
 *
 * @package     OAuth
 * @copyright   2010 ayumusato.com
 * @license     MIT License
 * @author      Ayumu Sato
 */
class OAuth_RequestToken extends OAuth_Client
{
    public function __construct($consumer, $token, $token_secret)
    {
        parent::__construct($consumer->key, $consumer->secret, $token, $token_secret);
    }
}

/**
 * AccessTokenを保持してる状態のOAuthオブジェクト
 *
 * @package     OAuth
 * @copyright   2010 ayumusato.com
 * @license     MIT License
 * @author      Ayumu Sato
 */
class OAuth_AccessToken extends OAuth_Client
{
    public function __construct($consumer, $token, $token_secret)
    {
        parent::__construct($consumer->key, $consumer->secret, $token, $token_secret);
    }
}

/**
 * OAuthのシグニチャ生成に必要なメソッド類を保持
 *
 * @package     OAuth
 * @copyright   2010 ayumusato.com
 * @license     MIT License
 * @author      Ayumu Sato
 */
class OAuth_Signature
{
    /**
     * シグニチャをHMAC-SHA1で生成
     *
     * @param string $base
     * @param string $consumer_secret
     * @param string $token_secret
     * @return string
     */
    public static function hmacSha1($base, $consumer_secret, $token_secret = '')
    {
        $keys   = array(
                        OAuth_Signature::rfc3986($consumer_secret),
                        OAuth_Signature::rfc3986($token_secret),
                        );

        $key    = implode('&', $keys);

        return base64_encode(hash_hmac('sha1', $base, $key, true));
    }

    /**
     * RFC3986に基づいてURLエンコード
     *
     * @param string $str
     * @return mixed
     */
    public static function rfc3986($str)
    {
        return str_replace('%7E', '~', rawurlencode($str));
    }

}