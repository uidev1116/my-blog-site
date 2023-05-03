<?php

class ACMS_POST_Api_Twitter_OAuth_LoginWithPin extends ACMS_POST_Api_Twitter
{
    function post()
    {
        $this->getAuthSession('tw_request', 'tw_blog_id', 'tw_user_id');
        $pin = $this->Post->get('oauth_verifier');
        $API    = ACMS_Services_Twitter::establish(BID, 'none');
        $session = Session::handle();
        $token = $session->get('tw_token');
        $secret = $session->get('tw_secret');
        ACMS_Services_Twitter::insertReqToken(BID, $token, $secret);
        // clear session
        $session->delete('tw_token');
        $session->delete('tw_secret');
        // request tokenの保持をチェック
        if ( count(ACMS_Services_Twitter::loadReqToken(BID)) == 2 ) {
            // access tokenの取得を試行
            $this->api = ACMS_Services_Twitter::establish(BID, 'request');
            $acs    = $this->api->getAcsToken(array('oauth_verifier' => $pin));
            $this->twid   = $acs['user_id'];
            if ($this->auth_type === 'login') {
                $url = $this->login();
            } else if ($this->auth_type === 'signup') {
                $url = $this->signup();
            } else if ($this->auth_type === 'addition') {
                $url = $this->addition();
            }
            redirect($url);
        }
    }
    /**
     * Twitterアカウントでログイン処理を実行する
     *
     */
    function login()
    {
        $user = loginAuthentication($this->twid, 'user_twitter_id');
        if ( $user === false ) {
            $this->loginFailed('login=failed');
            return false;
        }

        $sid        = generateSession($user);   // generate session id
        $bid        = intval($user['user_blog_id']);
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
            'sid'           => $sid,
            'query'         => array(),
        ));
    }

    /**
     * Twitterアカウントでサインアップ処理を行う
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
        $sid = generateSession($all[0]);

        return acmsLink(array(
            'protocol'      => (SSL_ENABLE and ('on' == config('login_ssl'))) ? 'https' : 'http',
            'bid'           => $this->auth_bid,
            'sid'           => $sid,
            'query'         => array(),
        ), false);
    }

    /**
     * 既存のユーザーにTwitterアカウントを結びつける
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
