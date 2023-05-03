<?php

namespace Acms\Services\SocialLogin;

use DB;
use SQL;
use ACMS_RAM;
use Acms\Services\Facades\Common;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Config;
use Acms\Services\Facades\Image;
use Acms\Services\Facades\Session;
use Acms\Services\SocialLogin\Exceptions\SocialLoginException;
use Acms\Services\SocialLogin\Exceptions\SocialLoginDuplicationException;

abstract class Base
{
    /**
     * @return string
     */
    abstract protected function getEmail();

    /**
     * @return string
     */
    abstract protected function getName();

    /**
     * @return string
     */
    abstract protected function getCode();

    /**
     * @return string
     */
    abstract protected function getId();

    /**
     * @return string
     */
    abstract protected function getIcon();

    /**
     * @return string user_id|user_facebook_id|user_twitter_id|user_google_id|user_line_id
     */
    abstract protected function getUserKey();

    /**
     * @param string $type facebook|twitter|google
     * @param int $bid
     * @param int|null $uid
     */
    public function setLoginType($type, $bid, $uid = null)
    {
        $session = Session::handle();
        $session->set('social_login_type', $type);
        $session->set('social_login_bid', $bid);
        if ($type === 'addition') {
            $session->set('social_login_uid', $uid);
        }
        $session->save();
    }

    /**
     * @return string
     */
    public function getLoginType()
    {
        $session = Session::handle();
        return $session->get('social_login_type');
    }

    /**
     * @return int
     */
    public function getLoginBid()
    {
        $session = Session::handle();
        return $session->get('social_login_bid');
    }

    /**
     * @return int
     */
    public function getLoginUid()
    {
        $session = Session::handle();
        return $session->get('social_login_uid');
    }

    /**
     * ログイン処理失敗のエラー画面にリダイレクト
     *
     * @param string $query リサイレクト先のクエリ
     */
    public function loginFailed($query = '')
    {
        if (!empty($query)) {
            $query = '?' . $query;
        }
        die(header('Location: ' . BASE_URL . LOGIN_SEGMENT . '/' . $query));
    }

    /**
     * CMSログイン処理を試みる
     */
    public function login()
    {
        $user = loginAuthentication($this->getId(), $this->getUserKey());
        if ($user === false) {
            throw new SocialLoginException();
        }

        generateSession($user);
        $bid = intval($user['user_blog_id']);
        $loginBid = BID;

        if (1
            && ('on' == $user['user_login_anywhere'] || roleAvailableUser())
            && !isBlogAncestor(BID, $bid, true)
        ) {
            $loginBid = $bid;
        }
        redirect(acmsLink(array(
            'bid' => $loginBid,
            'query' => array(),
        )));
    }

    /**
     * CMSサインアップ処理を試みる
     */
    public function signUp($bid)
    {
        if (Config::loadBlogConfigSet($bid)->get('snslogin') !== 'on') {
            throw new SocialLoginException();
        }
        // duplicate check
        $all = getUser($this->getId(), $this->getUserKey());
        if (0 < count($all)) {
            throw new SocialLoginDuplicationException();
        }
        // create account
        $this->createUser($bid);
        // get user data
        $all = getUser($this->getId(), $this->getUserKey());
        if (empty($all) || 1 < count($all)) {
            throw new SocialLoginException();
        }
        // generate session id
        generateSession($all[0]);

        redirect(acmsLink(array(
            'bid' => $bid,
            'query' => array(),
        ), false));
    }

    /**
     * 既存のユーザーにSNSアカウントを結びつける処理を試みる
     * @param int $uid
     * @param int $bid
     */
    public function addition($uid, $bid)
    {
        $query = array('edit' => 'update');
        // access restricted
        if (!SUID) {
            $query['auth'] = 'failed';
        }
        // sns auth check
        if (!snsLoginAuth($uid, $bid)) {
            $query['auth'] = 'failed';
        }
        // authentication
        $SQL = SQL::newSelect('user');
        $SQL->addSelect('user_id');
        $SQL->addWhereOpr($this->getUserKey(), $this->getId());
        $all = DB::query($SQL->get(dsn()), 'all');
        // double
        if (0 < count($all)) {
            $query['auth'] = 'double';
        }
        if (!isset($query['auth'])) {
            $SQL = SQL::newUpdate('user');
            $SQL->addUpdate($this->getUserKey(), $this->getId());
            $SQL->addWhereOpr('user_id', $uid);
            DB::query($SQL->get(dsn()), 'exec');
            ACMS_RAM::user($uid, null);
        }
        redirect(acmsLink(array(
            'bid' => $bid,
            'uid' => $uid,
            'admin' => 'user_edit',
            'query' => $query,
        ), false));
    }

    /**
     * 画像URIから画像を生成
     *
     * @param string $image_uri 画像URL
     * @return string 画像パス
     */
    protected function userIconFromUri($image_uri)
    {
        $img_path = '';
        try {
            $md5 = md5($image_uri);
            $squarePath = Storage::archivesDir() . 'square64-' . $md5 . '.jpg';
            if (Storage::exists(ARCHIVES_DIR . $squarePath)) {
                return $squarePath;
            }
            $src = @file_get_contents($image_uri);

            $img_path = Storage::archivesDir() . $md5 . '.jpg';
            Storage::makeDirectory(dirname(ARCHIVES_DIR . $img_path));
            Storage::put(ARCHIVES_DIR . $img_path, $src);

            Image::copyImage(ARCHIVES_DIR . $img_path, ARCHIVES_DIR . $squarePath, 220, 220, 220);
            Storage::remove(ARCHIVES_DIR . $img_path);

            $img_path = $squarePath;
        } catch (\Exception $e) {}

        return $img_path;
    }

    /**
     * @param int $bid
     * @return bool
     */
    public function createUser($bid)
    {
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_id');
        $sql->addWhereOpr('user_mail', $this->getEmail());
        if (DB::query($sql->get(dsn(), 'one'))) {
            return false;
        }
        $sql = SQL::newSelect('user');
        $sql->setSelect('user_sort');
        $sql->setOrder('user_sort', 'DESC');
        $sql->addWhereOpr('user_blog_id', $bid);
        $sort = intval(DB::query($sql->get(dsn()), 'one')) + 1;
        $uid = DB::query(SQL::nextval('user_id', dsn()), 'seq');

        $sql = SQL::newInsert('user');
        $sql->addInsert('user_id', $uid);
        $sql->addInsert('user_sort', $sort);
        $sql->addInsert('user_generated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        $sql->addInsert('user_blog_id', $bid);
        $sql->addInsert('user_code', $this->getCode());
        $sql->addInsert('user_status', 'open');
        $sql->addInsert('user_name', $this->getName());
        $sql->addInsert('user_pass', Common::genPass(8));
        $sql->addInsert($this->getUserKey(), $this->getId());
        $sql->addInsert('user_mail', $this->getEmail());
        $sql->addInsert('user_mail_magazine', 'off');
        $sql->addInsert('user_mail_mobile_magazine', 'off');
        $sql->addInsert('user_icon', $this->getIcon());
        $sql->addInsert('user_auth', config('subscribe_auth', 'subscriber'));
        $sql->addInsert('user_indexing', 'on');
        $sql->addInsert('user_login_anywhere', 'off');
        $sql->addInsert('user_login_expire', '9999-12-31');
        $sql->addInsert('user_updated_datetime', date('Y-m-d H:i:s', REQUEST_TIME));
        DB::query($sql->get(dsn()), 'exec');

        return true;
    }
}
