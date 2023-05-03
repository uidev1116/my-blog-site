<?php

class ACMS_GET_Api_Twitter_OAuthLoginCallback extends ACMS_GET_Api_Twitter
{
    var $api        = null;
    var $user       = null;
    var $twid       = null;

    function get()
    {
        $this->getAuthSession('tw_request', 'tw_blog_id', 'tw_user_id');

        $session = Session::handle();
        $Config = Config::loadBlogConfigSet(BID);
        $code = $this->Get->get('oauth_verifier');

        // token check
        if ( 0
            || $session->get('tw_token') !== $this->Get->get('oauth_token')
            || empty($this->auth_type)
            || !in_array($this->auth_type, array('login', 'signup', 'addition'))
        ) {
            $this->loginFailed('login=failed');
            return false;
        }

        // get access token
        $this->api = new Services_Twitter(
            $Config->get('twitter_sns_login_consumer_key'),
            $Config->get('twitter_sns_login_consumer_secret'),
            $session->get('tw_token'),
            $session->get('tw_secret'),
            'request'
        );

        // clear session
        $session->delete('tw_token');
        $session->delete('tw_secret');

        $access_token = $this->api->getAcsToken(array('oauth_verifier' => $code));
        $this->twid   = $access_token['user_id'];

        if ( $this->auth_type === 'login' ) {
            $url = $this->login();

        } else if ( $this->auth_type === 'signup' ) {
            $url = $this->signup();

        } else if ( $this->auth_type === 'addition' ) {
            $url = $this->addition();
        }
        redirect($url);
    }

    /**
     * tiwtterアカウントでログイン処理を実行する
     *
     */
    function login()
    {
        $user = loginAuthentication($this->twid, 'user_twitter_id');
        if ( $user === false ) {
            $this->loginFailed('login=failed');
            return false;
        }
        generateSession($user);   // generate session id
        $bid = intval($user['user_blog_id']);
        $login_bid  = BID;

        if ( 1
            and ( 'on' == $user['user_login_anywhere'] || roleAvailableUser() )
            and !isBlogAncestor(BID, $bid, true)
        ) {
            $login_bid   = $bid;
        }

        return acmsLink(array(
            'protocol'      => (SSL_ENABLE and ('on' == config('login_ssl'))) ? 'https' : 'http',
            'bid'           => $login_bid,
            'query'         => array(),
        ));
    }

    /**
     * tiwtterアカウントでサインアップ処理を行う
     *
     */
    function signup()
    {
        // sns auth check
        if ( Config::loadBlogConfigSet($this->auth_bid)->get('snslogin') !== 'on' ) {
            $this->loginFailed('auth=failed');
            return false;
        }

        // account info
        $this->api->httpRequest('account/verify_credentials.json', array(), 'GET');
        $this->user = json_decode($this->api->Response->getResponseBody());

        // duplicate check
        $all = getUser($this->twid, 'user_twitter_id');
        if ( 0 < count($all) ) {
            $this->loginFailed('auth=double');
            return false;
        }

        // create account
        $account = $this->extractAccountData($this->user);
        if ( $icon_uri = $this->user->profile_image_url ) {
            $account['icon'] = $this->userIconFromUri(preg_replace('/_normal/', '', $icon_uri));
        }
        $this->addUserFromOauth($account);

        // get user data
        $all = getUser($this->twid, 'user_twitter_id');
        if ( empty($all) || 1 < count($all) ) {
            $this->loginFailed('auth=double');
            return false;
        }

        // generate session id
        generateSession($all[0]);

        return acmsLink(array(
            'protocol'      => (SSL_ENABLE and ('on' == config('login_ssl'))) ? 'https' : 'http',
            'bid'           => $this->auth_bid,
            'query'         => array(),
        ), false);
    }

    /**
     * 既存のユーザーにtwitterアカウントを結びつける
     *
     */
    function addition()
    {
        $DB     = DB::singleton(dsn());
        $query  = array('edit' => 'update');

        // access restricted
        if ( !SUID ) {
            $query['auth'] = 'failed';
        }

        // sns auth check
        if ( !snsLoginAuth($this->auth_uid, $this->auth_bid) ) {
            $this->loginFailed('auth=failed');
            return false;
        }

        // authentication
        $SQL = SQL::newSelect('user');
        $SQL->addSelect('user_id');
        $SQL->addWhereOpr('user_twitter_id', $this->twid);
        $all = $DB->query($SQL->get(dsn()), 'all');

        // double
        if ( 0 < count($all) ) {
            $query['auth'] = 'double';
        }

        if ( !isset($query['auth']) ) {
            $SQL = SQL::newUpdate('user');
            $SQL->addUpdate('user_twitter_id', $this->twid);
            $SQL->addWhereOpr('user_id', $this->auth_uid);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::user($this->auth_uid, null);
        }

        return acmsLink(array(
            'bid' => $this->auth_bid,
            'uid' => $this->auth_uid,
            'admin' => 'user_edit',
            'query' => $query,
        ), false);
    }
}
