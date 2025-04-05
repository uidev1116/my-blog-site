<?php

namespace Acms\Services\Media;

use DB;
use SQL;
use ACMS_Filter;
use Acms\Services\Facades\Auth;
use Acms\Services\Facades\Common;
use Acms\Services\Facades\Session;

class Download
{
    /**
     * @var int
     */
    protected $mid;

    /**
     * @var array{
     *   path: string,
     *   name: string,
     *   extension: string,
     *   status: string
     * }
     */
    protected $media;

    /**
     * Download constructor.
     * @param $media array
     */
    public function __construct($media)
    {
        @set_time_limit(0);

        $this->mid = intval($media['mid']);
        $this->media = $media;
    }

    /**
     * ファイルダウンロード
     *
     * @return never
     */
    public function download()
    {
        $path = MEDIA_STORAGE_DIR . $this->media['path'];
        $filename = $this->media['name'];
        $extension = strtolower($this->media['extension']);

        if (in_array($extension, configArray('media_inline_download_extension'), true)) {
            Common::download($path, $filename, $extension, false);
        }
        Common::download($path, $filename, false, false);
    }

    /**
     * 該当のメディアにアクセス権があるか確認
     *
     * @return bool
     */
    public function validate()
    {
        $status = $this->media['status'] ?: 'entry';

        if ($status === 'entry') {
            return $this->validateEntryType();
        } elseif ($status === 'close') {
            return $this->validateCloseType();
        } elseif ($status === 'secret') {
            return $this->validateSecretType();
        } elseif ($status === 'open') {
            return true;
        }
        return false;
    }

    /**
     * メディアステータスがログイン限定の場合のバリデート
     *
     * @return bool
     */
    protected function validateSecretType()
    {
        if (!!SUID) {
            return true;
        }
        return false;
    }

    /**
     * メディアステータスがエントリー依存の場合のバリデート
     *
     * @return bool
     */
    protected function validateEntryType()
    {
        if ($this->validateCloseType()) {
            return true;
        }
        $entryIds = $this->findEntriesUseMedia();
        if (empty($entryIds)) {
            return true;
        }
        $sql = SQL::newSelect('entry');
        $sql->addSelect('entry_id');
        $sql->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        ACMS_Filter::entrySession($sql);
        ACMS_Filter::blogStatus($sql);
        $sql->addWhereIn('entry_id', $entryIds);
        $sql->setLimit(1);
        if (DB::query($sql->get(dsn()), 'row')) {
            return true;
        }
        return false;
    }

    /**
     * メディアステータスがCloseの場合のバリデート
     *
     * @return bool
     */
    protected function validateCloseType()
    {
        $session = Session::handle();
        $inPreviewLimit = $session->get('in-preview', REQUEST_TIME + (60 * 15));
        if ($inPreviewLimit && intval($inPreviewLimit) > REQUEST_TIME) {
            return true;
        }
        if (SUID && (Auth::isEditor(SUID) || Auth::isAdministrator(SUID) || Auth::isContributor(SUID))) {
            return true;
        }
        return false;
    }

    /**
     * 該当のメディアが使われているエントリーを検索
     *
     * @return array
     */
    protected function findEntriesUseMedia()
    {
        $entryIds = [];
        $entryIds = array_merge($entryIds, $this->findUnitsUseMedia());
        $entryIds = array_merge($entryIds, $this->findFieldsUseMedia());
        $entryIds = array_merge($entryIds, $this->findCustomUnitsUseMedia());

        return array_unique($entryIds);
    }

    /**
     * 該当のメディアが使われているユニットを検索
     *
     * @return array
     */
    protected function findUnitsUseMedia()
    {
        $sql = SQL::newSelect('column');
        $sql->addSelect('column_entry_id');
        $sql->addWhereOpr('column_type', 'media%', 'LIKE');
        $sql->addWhereOpr('column_field_1', $this->mid);

        return DB::query($sql->get(dsn()), 'list') ?: [];
    }

    /**
     * 該当のメディアが使われているフィールドを検索
     *
     * @return array
     */
    protected function findFieldsUseMedia()
    {
        $sql = SQL::newSelect('field');
        $sql->addSelect('field_eid');
        $sql->addWhereOpr('field_eid', null, '<>');
        $sql->addWhereOpr('field_key', '%@media', 'LIKE');
        $sql->addWhereOpr('field_value', $this->mid);

        return DB::query($sql->get(dsn()), 'list') ?: [];
    }

    /**
     * 該当のメディアが使われているカスタムユニットを検索
     *
     * @return array
     */
    protected function findCustomUnitsUseMedia()
    {
        $entryIds = [];
        $db = DB::singleton(dsn());
        $sql = SQL::newSelect('column');
        $sql->addSelect('column_entry_id');
        $sql->addSelect('column_field_6');
        $sql->addWhereOpr('column_type', 'custom%', 'LIKE');
        $q = $sql->get(dsn());
        $db->query($q, 'fetch');

        while ($unit = $db->fetch($q)) {
            $field = acmsDangerUnserialize($unit['column_field_6']);
            if (empty($field)) {
                continue;
            }
            foreach ($field->listFields() as $fd) {
                if (strpos($fd, '@media') !== false && in_array(strval($this->mid), $field->getArray($fd), true)) {
                    $entryIds[] = intval($unit['column_entry_id']);
                }
            }
        }
        return $entryIds;
    }
}
