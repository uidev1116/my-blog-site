<?php

class ACMS_POST_Login_Auth extends ACMS_POST_Login
{
    /**
     * CSRF対策
     *
     * @var bool
     */
    protected $isCSRF = true;

    /**
     * 二重送信対策
     *
     * @var bool
     */
    protected $checkDoubleSubmit = true;

    /**
     * @var \Field
     */
    protected $input;

    /**
     * @var string
     */
    protected $lockKey;

    /**
     * Main
     */
    public function post()
    {
        $this->input = $this->extract('login');
        $inputId = preg_replace("/(\s|　)/", "", $this->input->get('mail'));
        $inputPassword = preg_replace("/(\s|　)/", "", $this->input->get('pass'));
        $this->lockKey = md5('Login_Auth' . $inputId);

        // ユーザー決定前のバリデート
        $this->preValidate($inputId);
        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }

        // ユーザー検索
        $all = $this->find($inputId, $inputPassword);

        // ユーザーが見つからない or 複数見つかった
        if (empty($all) || 1 < count($all)) {
            $this->input->setValidator('pass', 'auth', false);
            Common::logLockPost($this->lockKey);
            return $this->Post;
        }

        // 一意のユーザー
        $user = $all[0];
        $uid = intval($user['user_id']);

        // ユーザー検索後のバリデート
        $this->postValidate($user);
        if (!$this->Post->isValidAll()) {
            return $this->Post;
        }

        // ２段階認証
        $inputCode = preg_replace("/(\s|　)/", "", $this->input->get('code'));
        if ($this->twoFactorAuthAction($uid, $inputCode)) {
            return $this->Post;
        }

        // DB更新
        $sql = SQL::newUpdate('user');
        $sql->addUpdate('user_pass_reset', '');
        $sql->addUpdate('user_login_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $sql->addWhereOpr('user_id', $uid);
        DB::query($sql->get(dsn()), 'exec');

        // セッション生成
        $sid = $this->generateSession($user);

        // リダイレクト処理
        $this->loginRedirect($sid, $user);
    }

    /**
     * セッション生成
     *
     * @param array $user
     * @return string
     */
    protected function generateSession($user)
    {
        return generateSession($user);
    }

    /**
     * ユーザー決定前のバリデート
     *
     * @param string $inputId
     */
    protected function preValidate($inputId)
    {
        if ('on' <> config('subscribe') and !!$this->Get->get('subscribe')) {
            $this->Get->delete('subscribe');
        }
        // access restricted
        if (SUID || !accessRestricted()) {
            $this->input->setMethod('pass', 'auth', false);
        }
        // CSRF
        if (isCSRF()) {
            $this->input->setMethod('pass', 'auth', false);
        }
        // 連続施行
        if ($inputId) {
            $trialTime = intval(config('login_trial_time', 5));
            $trialNumber = intval(config('login_trial_number', 5));
            $lockTime = intval(config('login_lock_time', 5));
            $lock = Common::validateLockPost($this->lockKey, $trialTime, $trialNumber, $lockTime);
            if ($lock === false) {
                $this->input->setMethod('mail', 'lock', false);
            }
        }
        $this->input->validate(new ACMS_Validator());
    }

    /**
     * ユーザー決定後のバリデート
     *
     * @param array $user
     */
    protected function postValidate($user)
    {
        // terminal restriction
        if (1
            && $user['user_auth'] !== 'administrator'
            && isset($user['user_login_terminal_restriction'])
            && $user['user_login_terminal_restriction'] === 'on'
        ) {
            $Cookie =& Field::singleton('cookie');
            if ($Cookie->get('acms_config_login_terminal_restriction') !== sha1('permission' . UA)) {
                $this->input->setValidator('mail', 'restriction', false);
            }
        }
        $this->input->validate(new ACMS_Validator());
    }

    /**
     * @param string $id
     * @param string $password
     * @return array
     */
    protected function find($id, $password)
    {
        if (empty($id) || empty($password)) {
            return array();
        }
        $sql = SQL::newSelect('user');
        $sql->addWhereOpr('user_status', 'open');
        $codeOrMail = SQL::newWhere();
        $codeOrMail->addWhereOpr('user_code', $id, '=', 'OR');
        $codeOrMail->addWhereOpr('user_mail', $id, '=', 'OR');
        $sql->addWhere($codeOrMail);
        $anywhereOrBid = SQL::newWhere();
        $anywhereOrBid->addWhereOpr('user_login_anywhere', 'on', '=', 'OR');
        $anywhereOrBid->addWhereOpr('user_blog_id', BID, '=', 'OR');
        $sql->addWhere($anywhereOrBid);
        $sql->addWhereOpr('user_login_expire', date('Y-m-d', REQUEST_TIME), '>=');
        $sql->addWhereOpr('user_confirmation_token', '');

        $all = DB::query($sql->get(dsn()), 'all');
        $all = array_filter($all, function ($user) use ($password) {
            return acmsUserPasswordVerify($password, $user['user_pass'], getPasswordGeneration($user));
        });
        return $all;
    }

    /**
     * 2段階認証
     *
     * @param $uid
     * @param $inputCode
     * @return boolean
     */
    protected function twoFactorAuthAction($uid, $inputCode)
    {
        if (!Tfa::isAvailableAccount($uid)) {
            return false;
        }
        if ($this->input->get('tfa') === 'on') {
            // 認証コード入力POST
            if (Tfa::verifyAccount($uid, $inputCode)) {
                // 認証OK
            } else {
                // 認証NG
                $this->input->setMethod('code', 'auth', false);
                $this->input->validate(new ACMS_Validator());
                if (!$this->Post->isValidAll()) {
                    return true;
                }
            }
        } else {
            // 認証コード入力画面を表示
            $this->input->set('tfa', 'on');
            return true;
        }
        return false;
    }

    /**
     * リダイレクト処理
     *
     * @param string $sid
     * @param array $user
     */
    protected function loginRedirect($sid, $user)
    {
        $loginBid = BID;
        $uid = intval($user['user_id']);
        $bid = intval($user['user_blog_id']);
        if (1
            && ('on' == $user['user_login_anywhere'] || roleAvailableUser())
            && !isBlogAncestor(BID, $bid, true)
        ) {
            $loginBid = $bid;
        }
        if (!!($redirect = $this->input->get('redirect')) & !preg_match('@^https?://@', $redirect)) {
            // リダイレクト指定（パス指定であること）
            if (preg_match('/^(.[^?]+)(.*)$/', $redirect, $matches)) {
                $path = $matches[1];
                $query_hash = $matches[2];
            } else {
                $path = $redirect;
                $query_hash = '';
            }
            $path = ltrim($path, '/');
            $url = (SSL_ENABLE ? 'https' : 'http') . '://'
                . HTTP_HOST . '/'
                . $path
                . (!empty($query_hash) ? $query_hash : '');
        } else {
            // ログインできるブログ一覧
            if (roleUserLoginAuth($user) && config('login_top_anywhere') == 'on') {
                $this->input->set('loginIndex', 'yes');

                $SQL = SQL::newSelect('usergroup_user', 'b');
                $SQL->addSelect('role_id', null, null, 'DISTINCT');
                $SQL->addSelect('role_blog_axis');
                $SQL->addLeftJoin('usergroup', 'usergroup_id', 'usergroup_id', 'a', 'b');
                $SQL->addLeftJoin('role', 'role_id', 'usergroup_role_id');
                $SQL->addWhereOpr('user_id', $uid);

                $loginBlog = array();

                if ($roleAry = DB::query($SQL->get(dsn()), 'all')) {
                    if (is_array($roleAry)) {
                        foreach ($roleAry as $role) {
                            $SQL = SQL::newSelect('role_blog');
                            $SQL->addSelect('blog_id');
                            $SQL->addWhereOpr('role_id', intval($role['role_id']));
                            $adminBlog = DB::query($SQL->get(dsn()), 'all');
                            if ($role['role_blog_axis'] === 'descendant') {
                                if (is_array($adminBlog)) {
                                    foreach ($adminBlog as $abid) {

                                        $SQL = SQL::newSelect('blog');
                                        $SQL->addSelect('blog_id');
                                        ACMS_Filter::blogTree($SQL, $abid['blog_id'], 'descendant-or-self');
                                        ACMS_Filter::blogStatus($SQL);
                                        $all = DB::query($SQL->get(dsn()), 'all');
                                        foreach ($all as $blog) {
                                            if (!array_search($blog['blog_id'], $loginBlog)) {
                                                $this->input->add('bid', $blog['blog_id']);
                                                $loginBlog[] = $blog['blog_id'];
                                            }
                                        }
                                    }
                                }
                            } else {
                                if (is_array($adminBlog)) {
                                    foreach ($adminBlog as $abid) {
                                        $this->input->add('bid', $abid['blog_id']);
                                    }
                                }
                            }
                        }
                    }
                }
                return $this->Post;
            } else {
                // 現在のURLにログイン
                if (config('login_auto_redirect') === 'on') {
                    $path = rtrim('/' . DIR_OFFSET . (REWRITE_ENABLE ? '' : SCRIPT_FILENAME), '/') . REQUEST_PATH;
                    $path = preg_replace('@' . LOGIN_SEGMENT . '$@', '', $path);
                    $query_hash = $_SERVER['QUERY_STRING'];
                    $path = ltrim($path, '/');
                    $url = (SSL_ENABLE ? 'https' : 'http') . '://'
                        . HTTP_HOST . '/'
                        . $path
                        . (!empty($query_hash) ? '?' . $query_hash : '');

                    // 管理ページ内にリダイレクト
                } else {
                    if ($admin = config('login_admin_path')) {
                        $url = acmsLink(array(
                            'protocol' => SSL_ENABLE ? 'https' : 'http',
                            'bid' => $loginBid,
                            'admin' => $admin,
                            'session' => in_array($user['user_auth'], array('administrator', 'editor', 'contributor')
                            ),
                        ), false);

                        // 通常のブログのトップページにリダイレクト
                    } else {
                        $url = acmsLink(array(
                            'protocol' => (SSL_ENABLE and ('on' == config('login_ssl'))) ? 'https' : 'http',
                            'bid' => $loginBid,
                            'query' => array(),
                        ));
                    }
                }
            }
        }

        $redirect_host = parse_url($url, PHP_URL_HOST);
        if (HTTP_HOST !== $redirect_host) {
            $protocol = HTTPS ? 'https://' : 'http://';
            $url = $protocol . HTTP_HOST . '?redirect=' . $url;
        }
        $url = htmlspecialchars_decode($url);
        $this->redirect($url);
    }
}
