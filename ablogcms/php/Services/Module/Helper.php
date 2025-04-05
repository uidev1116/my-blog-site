<?php

namespace Acms\Services\Module;

use Acms\Services\Facades\Database as DB;
use SQL;
use ACMS_Filter;
use Acms\Services\Facades\Image;
use Acms\Services\Facades\Common;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Preview;

class Helper
{
    private const DEFAULT_ALLOWED_MULTIPLE_ARGUMENTS_MODULE_NAMES = [
        'Entry_Body',
        'Entry_Summary',
        'Entry_List',
        'Entry_Headline',
        'Entry_Photo',
        'Entry_TagRelational',
        'Entry_GeoList',
        'Admin_Entry_Autocomplete',
        'Tag_Cloud',
        'Tag_Filter',
    ];

    /**
     * 重複チェック
     *
     * @param string $identifier モジュールID
     * @param int $mid
     * @param string $scope
     * @param int $bid
     *
     * @return bool
     */
    public function double($identifier, $mid, $scope, $bid = BID)
    {
        $DB = DB::singleton(dsn());

        //---------
        // sibling
        $SQL = SQL::newSelect('module');
        $SQL->addSelect('module_id');
        $SQL->addWhereOpr('module_identifier', $identifier);
        $SQL->addWhereOpr('module_blog_id', $bid);
        if (!empty($mid)) {
            $SQL->addWhereOpr('module_id', $mid, '<>');
        }
        if (!!$DB->query($SQL->get(dsn()), 'one')) {
            return false;
        }

        //----------
        // ancestor
        $SQL = SQL::newSelect('module');
        $SQL->addLeftJoin('blog', 'blog_id', 'module_blog_id');
        ACMS_Filter::blogTree($SQL, $bid, 'ancestor');
        $SQL->addSelect('module_id');
        $SQL->addWhereOpr('module_identifier', $identifier);
        $SQL->addWhereOpr('module_scope', 'global');
        if (!!$DB->query($SQL->get(dsn()), 'one')) {
            return false;
        }
        if ('local' == $scope) {
            return true;
        }

        //------------
        // descendant
        $SQL = SQL::newSelect('module');
        $SQL->addLeftJoin('blog', 'blog_id', 'module_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'descendant');
        $SQL->addSelect('module_id');
        $SQL->addWhereOpr('module_identifier', $identifier);

        return !$DB->query($SQL->get(dsn()), 'one');
    }

    /**
     * モジュールの複製
     *
     * @param $mid
     *
     * @return int
     */
    public function dup($mid)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('module');
        $SQL->addWhereOpr('module_id', $mid);
        $SQL->addWhereOpr('module_blog_id', BID);
        $base = $DB->query($SQL->get(dsn()), 'row');

        $SQL = SQL::newSelect('config');
        $SQL->addWhereOpr('config_module_id', $mid);
        $SQL->addWhereOpr('config_blog_id', BID);
        $config = $DB->query($SQL->get(dsn()), 'all');

        // fetch next id
        $new = $DB->query(SQL::nextval('module_id', dsn()), 'seq');

        $base['module_id']          = $new;
        $base['module_identifier'] .= config('module_identifier_duplicate_suffix') . $new;
        $base['module_label']      .= config('module_label_duplicate_suffix');

        // if Banner Module
        if ($base['module_name'] == 'Banner') {
            foreach ($config as $i => $row) {
                if ($row['config_key'] !== 'banner_img' || empty($row['config_value'])) {
                    continue;
                }

                $from_path  = $row['config_value'];

                $pathinfo   = pathinfo($from_path);
                $extension  = '.' . $pathinfo['extension'];
                $to_path    = sprintf('%03d', BID) . '/' . date('Ym') . '/' . uniqueString() . $extension;

                Image::copyImage(ARCHIVES_DIR . $from_path, ARCHIVES_DIR . $to_path);
                $config[$i]['config_value'] = $to_path;
            }
        }

        //-------
        // module
        $SQL = SQL::newInsert('module');
        foreach ($base as $key => $val) {
            $SQL->addInsert($key, $val);
        }
        $DB->query($SQL->get(dsn()), 'exec');

        //-------
        // config
        foreach ($config as $row) {
            $row['config_module_id']    = $new;
            $SQL    = SQL::newInsert('config');
            foreach ($row as $key => $val) {
                $SQL->addInsert($key, $val);
            }
            $DB->query($SQL->get(dsn()), 'exec');
        }

        //-------
        // field
        $Field = loadModuleField($mid);
        foreach ($Field->listFields() as $fd) {
            if (
                1
                and !strpos($fd, '@path')
                and !strpos($fd, '@tinyPath')
                and !strpos($fd, '@largePath')
                and !strpos($fd, '@squarePath')
            ) {
                continue;
            }
            $set = false;
            foreach ($Field->getArray($fd, true) as $i => $path) {
                if (!Storage::isFile(ARCHIVES_DIR . $path)) {
                    continue;
                }
                $info       = pathinfo($path);
                $dirname    = empty($info['dirname']) ? '' : $info['dirname'] . '/';
                Storage::makeDirectory(ARCHIVES_DIR . $dirname);
                $ext        = empty($info['extension']) ? '' : '.' . $info['extension'];
                $newPath    = $dirname . uniqueString() . $ext;
                Storage::copy(ARCHIVES_DIR . $path, ARCHIVES_DIR . $newPath);
                if (!$set) {
                    $Field->delete($fd);
                    $set = true;
                }
                $Field->add($fd, $newPath);
            }
        }
        Common::saveField('mid', $new, $Field);

        return $new;
    }

    /**
     * 複数引数を許可するモジュールかどうか
     *
     * @param \Field $Module
     *
     * @return bool
     */
    public function isAllowedMultipleArguments(\Field $Module): bool
    {
        $allowedMultipleArgsModuleNames = array_unique(
            array_merge(
                self::DEFAULT_ALLOWED_MULTIPLE_ARGUMENTS_MODULE_NAMES,
                configArray('module_allow_multiple_arguments')
            )
        );
        return in_array($Module->get('name'), $allowedMultipleArgsModuleNames, true);
    }

    /**
     * 現在ログイン中のユーザーがモジュールの一括ブログ変更を許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canBulkBlogChange(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (roleAvailableUser($suid)) {
            if (roleAuthorization('admin_etc', $blogId)) {
                return true;
            }
            return false;
        }
        if (sessionWithAdministration($blogId)) {
            return true;
        }
        return false;
    }

    /**
     * 現在ログイン中のユーザーがモジュールの一括削除を許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canBulkDelete(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (roleAvailableUser($suid)) {
            if (roleAuthorization('module_edit', $blogId)) {
                return true;
            }
            return false;
        }
        if (sessionWithContribution($blogId)) {
            return true;
        }
        return false;
    }

    /**
     * 現在ログイン中のユーザーがモジュールの一括エクスポートを許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canBulkExport(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (sessionWithAdministration($blogId)) {
            return true;
        }
        return false;
    }

    /**
     * 現在ログイン中のユーザーがモジュールの一括ステータス変更を許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canBulkStatusChange(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (roleAvailableUser($suid)) {
            if (roleAuthorization('module_edit', $blogId)) {
                return true;
            }

            return false;
        }

        if (sessionWithAdministration($blogId)) {
            return true;
        }

        return false;
    }

    /**
     * 現在ログイン中のユーザーがモジュールの削除を許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canDelete(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (roleAvailableUser($suid)) {
            if (roleAuthorization('module_edit', $blogId)) {
                return true;
            }
            return false;
        }
        if (sessionWithAdministration($blogId)) {
            return true;
        }
        return false;
    }

    /**
     * 現在ログイン中のユーザーがモジュールの複製を許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canDuplicate(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (sessionWithAdministration($blogId)) {
            return true;
        }
        return false;
    }

    /**
     * 現在ログイン中のユーザーがモジュールの更新を許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canUpdate(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (roleAvailableUser($suid)) {
            if (roleAuthorization('module_edit', $blogId)) {
                return true;
            }
            return false;
        }
        if (sessionWithAdministration($blogId)) {
            return true;
        }
        return false;
    }

    /**
     * 現在ログイン中のユーザーがモジュールの作成を許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canCreate(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (roleAvailableUser($suid)) {
            if (roleAuthorization('module_edit', $blogId)) {
                return true;
            }
            return false;
        }
        if (sessionWithAdministration($blogId)) {
            return true;
        }
        return false;
    }

    /**
     * 現在ログイン中のユーザーがモジュールのエクスポートを許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canExport(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (sessionWithAdministration($blogId)) {
            return true;
        }
        return false;
    }

    /**
     * 現在ログイン中のユーザーがモジュールのインポートを許可されているかどうか
     *
     * @param int $blogId
     * @return bool
     */
    public function canImport(int $blogId): bool
    {
        /** @var int|null $suid */
        $suid = SUID;
        if (is_null($suid)) {
            return false;
        }

        if (Preview::isPreviewMode()) {
            return false;
        }
        if (sessionWithAdministration($blogId)) {
            return true;
        }
        return false;
    }
}
