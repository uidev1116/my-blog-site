<?php

namespace Acms\Services\Auth;

use DB;
use SQL;
use ACMS_Filter;
use ACMS_RAM;
use Acms\Services\Facades\Config;

class General implements Contracts\Guard
{
    /**
     * 指定ユーザーが読者か
     *
     * @param int|null $uid
     * @return bool
     */
    public function isSubscriber($uid=SUID)
    {
        if ( !$uid ) return false;
        return 'subscriber' === ACMS_RAM::userAuth($uid);
    }

    /**
     * 指定したユーザーが投稿者か
     *
     * @param int|null $uid
     * @return bool
     */
    public function isContributor($uid=SUID)
    {
        if ( !$uid ) return false;
        return 'contributor' === ACMS_RAM::userAuth($uid);
    }

    /**
     * 指定したユーザーが編集者か
     *
     * @param int|null $uid
     * @return bool
     */
    public function isEditor($uid=SUID)
    {
        if ( !$uid ) return false;
        return 'editor' === ACMS_RAM::userAuth($uid);
    }

    /**
     * 指定したユーザーが管理者か
     *
     * @param int|null $uid
     * @return bool
     */
    public function isAdministrator($uid=SUID)
    {
        if ( !$uid ) return false;
        return 'administrator' === ACMS_RAM::userAuth($uid);
    }

    /**
     * ログイン中のユーザーがそのブログにおいて読者以上の権限があるか
     *
     * @param int|null $bid
     * @return bool
     */
    public function isPermissionOfSubscriber($bid=BID)
    {
        if ( !$this->isControlBlog($bid) ) return false;

        switch ( ACMS_RAM::userAuth(SUID) ) {
            case 'administrator':
            case 'editor':
            case 'contributor':
            case 'subscriber':
                break;
            default:
                return false;
        }
        return true;
    }

    /**
     * ログイン中のユーザーがそのブログにおいて投稿者以上の権限があるか
     *
     * @param int|null $bid
     * @return bool
     */
    public function isPermissionOfContributor($bid=BID)
    {
        if ( !$this->isControlBlog($bid) ) return false;

        switch ( ACMS_RAM::userAuth(SUID) ) {
            case 'administrator':
            case 'editor':
            case 'contributor':
                break;
            default:
                return false;
        }
        return true;
    }

    /**
     * ログイン中のユーザーがそのブログにおいて編集者以上の権限があるか
     *
     * @param int|null $bid
     * @return bool
     */
    public function isPermissionOfEditor($bid=BID)
    {
        if ( !$this->isControlBlog($bid) ) return false;
        switch ( ACMS_RAM::userAuth(SUID) ) {
            case 'administrator':
            case 'editor':
                break;
            default:
                return false;
        }
        return true;
    }

    /**
     * ログイン中のユーザーがそのブログにおいて管理者以上の権限があるか
     *
     * @param int|null $bid
     * @return bool
     */
    public function isPermissionOfAdministrator($bid=BID)
    {
        if ( !$this->isControlBlog($bid) ) return false;
        return $this->isAdministrator();
    }

    /**
     * 指定したユーザーがSNSログインを利用できるか
     *
     * @param int|null $uid
     * @param int|null $bid
     * @return bool
     */
    public function isPermissionOfSnsLogin($uid=SUID, $bid=BID)
    {
        $Config = Config::loadBlogConfigSet($bid);

        if ( $Config->get('snslogin') !== 'on' ) { return false; }
        $auth = ACMS_RAM::userAuth($uid);

        switch ( $Config->get('snslogin_auth') ) {
            case 'subscriber':
                if ( in_array($auth, array('contributor', 'editor', 'administrator')) ) { return false; }
                break;
            case 'contributor':
                if ( in_array($auth, array('editor', 'administrator')) ) { return false; }
                break;
            case 'editor':
                if ( in_array($auth, array('administrator')) ) { return false; }
                break;
            case 'administrator':
                break;
            default:
                return false;
        }
        return true;
    }

    /**
     * ログインしているユーザーが特定の管理ページで権限があるかチェック
     *
     * @param string $action
     * @param string $admin
     * @param string $idKey
     * @param string $id
     *
     * @return bool
     */
    public function checkShortcut($action, $admin, $idKey, $id)
    {
        $admin = str_replace('/', '_', $admin);

        $aryAuth = array();
        if ( sessionWithContribution() ) $aryAuth[] = 'contribution';
        if ( sessionWithCompilation() ) $aryAuth[] = 'compilation';
        if ( sessionWithAdministration() ) $aryAuth[] = 'administration';

        $key = '';
        if ( $idKey ) {
            $key .= ('_' . $idKey);
        }
        if ( $id ) {
            $key .= ('_' . $id);
        }

        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('dashboard');
        $SQL->setSelect('dashboard_key');
        $SQL->addWhereOpr('dashboard_key', 'shortcut' . $key . '_' . $admin . '_auth');
        $SQL->addWhereIn('dashboard_value', $aryAuth);
        $SQL->addWhereOpr('dashboard_blog_id', BID);

        return !!$DB->query($SQL->get(dsn()), 'one');
    }

    /**
     * ログイン中のユーザーがそのブログにおいて権限があるか
     *
     * @param int $bid
     * @return bool
     */
    protected function isControlBlog($bid)
    {
        if ( 1
            and ACMS_RAM::userGlobalAuth(SUID) !== 'on'
            and SBID !== intval($bid)
        ) {
            return false;
        }

        if ( !(1
            and ACMS_RAM::blogLeft(SBID) <= ACMS_RAM::blogLeft($bid)
            and ACMS_RAM::blogRight(SBID) >= ACMS_RAM::blogRight($bid)
        ) ) {
            return false;
        }

        return true;
    }

    /**
     * 指定したユーザーの権限があるブログリストを取得
     *
     * @param int $uid
     * @return array
     */
    public function getAuthorizedBlog($uid)
    {
        if (ACMS_RAM::userGlobalAuth($uid) !== 'on') {
            return array(SBID);
        }
        $SQL = SQL::newSelect('blog');
        $SQL->setSelect('blog_id');
        ACMS_Filter::blogTree($SQL, SBID, 'self-descendant');
        if (!$this->isAdministrator($uid)) {
            $SQL->addWhereOpr('blog_status', 'close', '<>');
        }
        return DB::query($SQL->get(dsn()), 'list');
    }
}
