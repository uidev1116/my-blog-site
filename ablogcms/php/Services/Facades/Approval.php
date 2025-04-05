<?php

namespace Acms\Services\Facades;

/**
 * Class Approval
 *
 * @method static int notificationCount() 承認通知数を取得する
 * @method static \SQL_Select buildSql() 承認通知の絞り込みSQLを取得する
 * @method static array getGroupList(int|null $uid) ユーザーのグループリストを取得する
 */
class Approval extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'approval';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
