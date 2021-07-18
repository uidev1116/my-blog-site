<?php

class ACMS_GET_Api_Google_OAuthLoginCallback extends ACMS_GET_Api_Google
{
    private $user;

    function get()
    {
        $this->getAuthSession('google_request', 'google_blog_id', 'google_user_id');

        $Session    = ACMS_Session::singleton();
        $Client     = $this->getGoogleClient();
        $code       = $this->Get->get('code');

        // get access token
        if ( !!$code ) {
            $Client->authenticate($code);
            $Session->set('access_token', $Client->getAccessToken());
            $Session->save();
            header('Location: ' . filter_var($this->redirect_uri, FILTER_SANITIZE_URL));
            die();
        }

        // access_token continue
        if ( $Session->get('access_token') ) {
            $Client->setAccessToken($Session->get('access_token'));
        } else {
            return false;
        }

        // get user info
        $Service = new Google_Service_Oauth2($Client);
        $this->user = $Service->userinfo->get();

        // clear session
        $Session->clear();

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
     * google ログイン処理を実行する
     *
     */
    function login()
    {
        $user = loginAuthentication($this->user->id, 'user_google_id');
        if ( $user === false ) {
            $this->loginFailed('login=failed');
            return false;
        }

        $sid        = generateSession($user);  // generate session id
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
     * googleアカウントでサインアップ処理を行う
     *
     */
    function signup()
    {
        // sns auth check
        if ( Config::loadBlogConfigSet($this->auth_bid)->get('snslogin') !== 'on' ) {
            $this->loginFailed('auth=failed');
            return false;
        }

        // duplicate check
        $all = getUser($this->user->id, 'user_google_id');
        if ( 0 < count($all) ) {
            $this->loginFailed('auth=double');
            return false;
        }

        // create account
        $account = $this->extractAccountData($this->user);
        $account['icon'] = $this->userIconFromUri($this->user->picture);
        $this->addUserFromOauth($account);

        // get user data
        $all = getUser($this->user->id, 'user_google_id');
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
     * 既存のユーザーにgoogleアカウントを結びつける
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
        $SQL    = SQL::newSelect('user');
        $SQL->addSelect('user_id');
        $SQL->addWhereOpr('user_google_id', $this->user->id);
        $all    = $DB->query($SQL->get(dsn()), 'all');

        // double
        if ( 0 < count($all) ) {
            $query['auth'] = 'double';
        }

        if ( !isset($query['auth']) ) {
            $SQL    = SQL::newUpdate('user');
            $SQL->addUpdate('user_google_id', $this->user->id);
            $SQL->addWhereOpr('user_id', $this->auth_uid);
            $DB->query($SQL->get(dsn()), 'exec');
        }

        return acmsLink(array(
            'protocol'  => (SSL_ENABLE and ('on' == config('login_ssl'))) ? 'https' : 'http',
            'bid'       => $this->auth_bid,
            'uid'       => $this->auth_uid,
            'admin'     => 'user_edit',
            'query'     => $query,
        ), false);
    }
}
