<?php

namespace Acms\Services\Entry;

use SQL;
use DB;
use Acms\Services\Contracts\Export as ExportBase;

class Export extends ExportBase
{
    /**
     * @var array
     */
    protected $tables;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var int[]
     */
    protected $targetEntryIds = [];

    /**
     * @var int[]
     */
    protected $targetCategoryIds = [];

    /**
     * @var int[]
     */
    protected $targetMediaIds = [];

    /**
     * @var int[]
     */
    protected $targetModuleIds = [];

    /**
     * @var string[]
     */
    protected $mediaFiles = [];

    /**
     * @var string[]
     */
    protected $storageFiles = [];

    /**
     * @var string[]
     */
    protected $archivesFiles = [];

    /**
     * Export constructor.
     */
    public function __construct()
    {
        $this->setTables([
            'entry',
            'column',
            'field',
            'tag',
            'entry_sub_category',
            'category',
            'media',
            'media_tag',
            'module',
        ]);
        $dsn = dsn();
        $this->prefix = $dsn['prefix'];
    }

    public function addEntry($eid)
    {
        $this->targetEntryIds[] = $eid;
    }

    /**
     * @param resource $fp
     * @return array
     */
    public function export($fp)
    {
        $queryList = [];

        foreach ($this->tables as $table) {
            $sql = SQL::newSelect($table);
            $method = 'getQuery' . ucfirst($table);
            if (is_callable([$this, $method])) {
                $sql = call_user_func_array([$this, $method], [$sql]);
            }
            $q = $sql->get(dsn());
            $queryList[$table] = $q;
        }
        $this->dumpYaml($fp, $queryList);

        return [
            'media' => $this->mediaFiles,
            'storage' => $this->storageFiles,
            'archives' => $this->archivesFiles,
        ];
    }

    /**
     * fix data
     *
     * @param array &$record
     * @param string $table
     *
     * @return void
     */
    protected function fix(&$record, $table)
    {
        if ($table === 'column' && 'text' === $record['column_type']) {
            $txt = $record['column_field_1'];
            $record['column_field_1'] = $this->fixNextLine($txt);
        }
    }

    protected function getQueryEntry($sql)
    {
        $sql->addWhereIn('entry_id', $this->targetEntryIds);
        $sql->addWhereOpr('entry_status', 'trash', '<>');
        $q = $sql->get(dsn());
        DB::query($q, 'fetch');

        while ($row = DB::fetch($q)) {
            $cid = intval($row['entry_category_id']);
            if ($cid > 0 && !in_array($cid, $this->targetCategoryIds, true)) {
                $this->targetCategoryIds[] = $cid;
            }
        }
        return $sql;
    }

    protected function getQueryColumn($sql)
    {
        $sql->addWhereIn('column_entry_id', $this->targetEntryIds);
        $q = $sql->get(dsn());
        DB::query($q, 'fetch');

        while ($row = DB::fetch($q)) {
            $type = detectUnitTypeSpecifier($row['column_type']);
            if ($type === 'image') {
                $this->archivesFiles[] = $row['column_field_2'];
                $this->archivesFiles[] = otherSizeImagePath($row['column_field_2'], 'large');
                $this->archivesFiles[] = otherSizeImagePath($row['column_field_2'], 'tiny');
                $this->archivesFiles[] = otherSizeImagePath($row['column_field_2'], 'square');
            }
            if ($type === 'file') {
                $this->archivesFiles[] = $row['column_field_2'];
            }
            if ($type === 'media') {
                $mediaId = intval($row['column_field_1']);
                if ($mediaId > 0 && !in_array($mediaId, $this->targetMediaIds, true)) {
                    $this->targetMediaIds[] = $mediaId;
                }
            }
            if ($type === 'module') {
                $this->targetModuleIds[] = intval($row['column_field_1']);
            }
            if ($type === 'custom') {
                $field = acmsUnserialize($row['column_field_6']);
                if (!($field instanceof \Field)) {
                    continue;
                }
                if (!method_exists($field, 'deleteField')) {
                    continue;
                }
                foreach ($field->listFields() as $fd) {
                    foreach ($field->getArray($fd, true) as $i => $val) {
                        if (empty($val)) {
                            continue;
                        }
                        if (strpos($fd, '@media') !== false) {
                            $mediaId = intval($val);
                            if ($mediaId > 0 && !in_array($mediaId, $this->targetMediaIds, true)) {
                                $this->targetMediaIds[] = $mediaId;
                            }
                        } elseif (
                            0
                            || strpos($fd, '@path')
                            || strpos($fd, '@tinyPath')
                            || strpos($fd, '@largePath')
                            || strpos($fd, '@squarePath')
                        ) {
                            $this->archivesFiles[] = $val;
                        }
                    }
                }
            }
        }

        return $sql;
    }

    protected function getQueryField($sql)
    {
        $sql->addWhereIn('field_eid', $this->targetEntryIds);
        $q = $sql->get(dsn());
        DB::query($q, 'fetch');

        while ($row = DB::fetch($q)) {
            $fd = $row['field_key'];
            // image or file
            if (
                0
                or strpos($fd, '@path')
                or strpos($fd, '@tinyPath')
                or strpos($fd, '@largePath')
                or strpos($fd, '@squarePath')
            ) {
                $this->archivesFiles[] = $row['field_value'];
            }
            // media
            if (strpos($fd, '@media') !== false) {
                $mediaId = intval($row['field_value']);
                if ($mediaId > 0 && !in_array($mediaId, $this->targetMediaIds, true)) {
                    $this->targetMediaIds[] = $mediaId;
                }
            }
        }
        return $sql;
    }

    protected function getQueryTag($sql)
    {
        $sql->addWhereIn('tag_entry_id', $this->targetEntryIds);

        return $sql;
    }

    protected function getQueryEntry_sub_category($sql)
    {
        $sql->addWhereIn('entry_sub_category_eid', $this->targetEntryIds);
        $q = $sql->get(dsn());
        DB::query($q, 'fetch');

        while ($row = DB::fetch($q)) {
            $cid = intval($row['entry_sub_category_id']);
            if ($cid > 0 && !in_array($cid, $this->targetCategoryIds, true)) {
                $this->targetCategoryIds[] = $cid;
            }
        }
        return $sql;
    }

    protected function getQueryCategory($sql)
    {
        $sql->addWhereIn('category_id', $this->targetCategoryIds);

        return $sql;
    }

    protected function getQueryMedia($sql)
    {
        $sql->addWhereIn('media_id', $this->targetMediaIds);
        $q = $sql->get(dsn());
        DB::query($q, 'fetch');

        while ($row = DB::fetch($q)) {
            $type = $row['media_type'];
            $path = $row['media_path'];
            $thumbnail = $row['media_thumbnail'];
            $original = $row['media_original'];

            if ($type === 'file') {
                if (!empty($path) && !in_array($path, $this->storageFiles, true)) {
                    $this->storageFiles[] = $path;
                }
                if (!empty($thumbnail) && !in_array($thumbnail, $this->mediaFiles, true)) {
                    $this->mediaFiles[] = $thumbnail;
                    $this->mediaFiles[] = otherSizeImagePath($thumbnail, 'large');
                    $this->mediaFiles[] = otherSizeImagePath($thumbnail, 'tiny');
                }
            } else {
                if (!empty($path) && !in_array($path, $this->mediaFiles, true)) {
                    $this->mediaFiles[] = $path;
                    $this->mediaFiles[] = otherSizeImagePath($path, 'large');
                    $this->mediaFiles[] = otherSizeImagePath($path, 'tiny');
                }
                if (!empty($original) && !in_array($original, $this->mediaFiles, true)) {
                    $this->mediaFiles[] = $original;
                    $this->mediaFiles[] = otherSizeImagePath($original, 'large');
                    $this->mediaFiles[] = otherSizeImagePath($original, 'tiny');
                }
            }
        }
        return $sql;
    }

    protected function getQueryModule($sql)
    {
        $sql->addWhereIn('module_id', $this->targetModuleIds);

        return $sql;
    }

    protected function getQueryMedia_tag($sql)
    {
        $sql->addWhereIn('media_tag_media_id', $this->targetMediaIds);

        return $sql;
    }
}
