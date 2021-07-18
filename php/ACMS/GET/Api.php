<?php

class ACMS_GET_Api extends ACMS_GET
{
    var $id;
    var $api;
    var $params;
    var $limit;
    var $crit;
    var $error_msg;

    var $auth_bid;      // 認証する時のBID
    var $auth_uid;      // 認証するUID
    var $auth_type;     // 認証するタイプ login | signup | addition
    var $redirect_uri;  // callback uri

    /**
     * SNSアカウントデータからユーザデータを抽出
     *
     * @abstract
     */
    // abstract public function extractAccountData();

    // リクエストURLをハッシュ化して，IDのユニークを保証している
    // その為，ブログIDは関係なく，均一に扱われる
    /**
     * キャッシュの読み込み，利用できるキャッシュがなければfalseを返す
     *
     * @param string $hash
     * @return bool|null|string
     */
    function detectCache($hash)
    {
        if ( !!DEBUG_MODE  || ('on' != config('cache')) ) return false;

        $DB     = DB::singleton(dsn());
        $expire = date('Y-m-d H:i:s');

        $SQL    = SQL::newSelect('cache');
        $SQL->setSelect('cache_data');
        $SQL->addWhereOpr('cache_id', $hash);
        $SQL->addWhereOpr('cache_expire', $expire, '>', 'AND');

        return gzdecode($DB->query($SQL->get(dsn()), 'one'));
    }

    /**
     * キャッシュを保存する
     *
     * @param string $hash
     * @param int $expire キャッシュの有効期限(秒)
     * @param mixed $rawData gzencode前のデータ
     * @return miexed
     */
    function saveCache($hash, $expire, $rawData)
    {
        if ( !!DEBUG_MODE  || ('on' != config('cache')) ) return false;

        $DB     = DB::singleton(dsn());
        $expire = date('Y-m-d H:i:s', strtotime('+'.$expire.' seconds'));

        $SQL    = SQL::newDelete('cache');
        $SQL->addWhereOpr('cache_id', $hash);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL = SQL::newInsert('cache');
        $SQL->addInsert('cache_id', $hash);
        $SQL->addInsert('cache_data', gzencode($rawData));
        $SQL->addInsert('cache_expire', $expire);

        return $DB->query($SQL->get(dsn()), 'exec');
    }

    function getHash($url = null)
    {
        if ( empty($url) ) {
            return md5($this->id.$this->api.serialize($this->params));
        } else {
            return md5($url);
        }
    }

    function resolveRequest(& $Tpl, $type)
    {
        $hash   = $this->getHash();

        // キャッシュ確認
        if ( !($response = $this->detectCache($hash)) ) {
            $response   = $this->apiRequest(strtolower($type));

            // 取得できたら保存
            if ( !empty($response) ) {
                $this->saveCache($hash, $this->crit, $response);
            }
        }

        // レスポンスをビルド
        if ( !empty($response) ) {
            $this->build($response, $Tpl);
        } else {
            $this->failed($Tpl);
        }
    }

    function apiRequest($type)
    {
        try {
            switch ($type) {
                case 'instagram':
                    if ( !($API = ACMS_Services_Instagram::establish($this->id)) ) {
                        throw new Exception('establish failed');
                    }
                break;
                case 'twitter':
                default:
                    if ( !($API = ACMS_Services_Twitter::establish($this->id)) ) {
                        throw new Exception('establish failed');
                    }
                break;
            }

            if ( !($API->httpRequest($this->api, $this->params)) ) {
                throw new Exception('transaction failed');
            }

            return $API->Response->getResponseBody();

        } catch (Exception $e) {
            $this->error_msg = $e->getMessage();
            return false;
        }
    }

    function failed(& $Tpl)
    {
        $Tpl->add('failed', array('error_msg' => $this->error_msg));
    }

    /**
     * セッションから認証タイプなどを取得
     *
     * @param string $auth_type_key 認証タイプのキー
     * @param string $auth_bid_key 認証のBIDのキー
     * @param string $auth_uid_key 認証のUIDのキー
     */
    function getAuthSession($auth_type_key, $auth_bid_key, $auth_uid_key)
    {
        $Session = ACMS_Session::singleton();
        
        $this->auth_type    = $Session->get($auth_type_key);
        $this->auth_bid     = $Session->get($auth_bid_key);
        $this->auth_uid     = $Session->get($auth_uid_key);

        if ( 0
            || empty($this->auth_bid)
            || empty($this->auth_type)
            || !in_array($this->auth_type, array('login', 'signup', 'addition'))
        ) {
            $this->loginFailed('auth=failed');
            return false;
        }
    }

    /**
     * ログイン処理失敗のエラー画面にリダイレクト
     *
     * @param string $query リサイレクト先のクエリ
     */
    function loginFailed($query='')
    {
        $Session = ACMS_Session::singleton();
        $Session->clear();

        if ( !empty($query) ) $query = '?'.$query;
        die(header('Location: '.BASE_URL.LOGIN_SEGMENT.'/'.$query));
    }

    /**
     * 画像URIから画像を生成
     *
     * @param string $image_uri 画像URL
     * @return string 画像パス
     */
    function userIconFromUri($image_uri)
    {
        $img_path = '';
        $POST = new ACMS_POST();

        try {
            $rsrc = Storage::get($image_uri);
            $extension  = substr(strrchr($image_uri, '.'), 1);
            $img_path   = $POST->archivesDir().uniqueString().'.jpg';
            $POST->setupDir(dirname(ARCHIVES_DIR.$img_path), intval(config('permission_dir')));
            Storage::put(ARCHIVES_DIR.$img_path, $rsrc);

            $resize_path = $POST->archivesDir().'square64-'.uniqueString().'.jpg';
            $POST->copyImage(ARCHIVES_DIR.$img_path, ARCHIVES_DIR.$resize_path, 64, 64, 64);
            Storage::remove(ARCHIVES_DIR.$img_path);

            $img_path = $resize_path;
        } catch ( \Exception $e ) {}

        return $img_path;
    }

    /**
     * 新しいユーザーをOAuth認証から作成
     *
     * @param array $data OAuth認証データ
     */
    function addUserFromOauth($data=array())
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('user');
        $SQL->setSelect('user_id');
        $SQL->addWhereOpr('user_mail', $data['email']);
        if ( $DB->query($SQL->get(dsn(), 'one')) ) {
            return false;
        }

        $SQL    = SQL::newSelect('user');
        $SQL->setSelect('user_sort');
        $SQL->setOrder('user_sort', 'DESC');
        $SQL->addWhereOpr('user_blog_id', $data['bid']);
        $sort   = intval($DB->query($SQL->get(dsn()), 'one')) + 1;
        $uid    = $DB->query(SQL::nextval('user_id', dsn()), 'seq');

        $SQL    = SQL::newInsert('user');
        $SQL->addInsert('user_id', $uid);
        $SQL->addInsert('user_sort', $sort);
        $SQL->addInsert('user_generated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $SQL->addInsert('user_blog_id', $data['bid']);
        $SQL->addInsert('user_code', $data['code']);
        $SQL->addInsert('user_status', 'open');
        $SQL->addInsert('user_name', $data['name']);
        $SQL->addInsert('user_pass', ACMS_POST::genPass(8));
        $SQL->addInsert($data['oauth_type'], $data['oauth_id']);
        $SQL->addInsert('user_mail', $data['email']);
        $SQL->addInsert('user_mail_magazine', 'off');
        $SQL->addInsert('user_mail_mobile_magazine', 'off');
        $SQL->addInsert('user_icon', $data['icon']);
        $SQL->addInsert('user_auth', config('subscribe_auth', 'subscriber'));
        $SQL->addInsert('user_indexing', 'on');
        $SQL->addInsert('user_login_anywhere', 'off');
        $SQL->addInsert('user_login_expire', '9999-12-31');
        $SQL->addInsert('user_updated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));

        $DB->query($SQL->get(dsn()), 'exec');
    }
}
