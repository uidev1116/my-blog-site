<?php

namespace Acms\Services\StaticExport;

use DB;
use SQL;
use ACMS_RAM;
use Acms\Services\Facades\Storage;

class CopyEntryArchive
{
    /**
     * @var array
     */
    protected $destinationPaths;

    /**
     * CopyEntryArchive constructor.
     * @param array $destinationPaths
     */
    public function __construct($destinationPaths)
    {
        $this->destinationPaths = $destinationPaths;
    }

    /**
     * @param int $eid
     */
    public function copy($eid)
    {
        $Field = loadEntryField($eid);
        $this->copyUnitArchives($eid);
        $this->fieldDupe($Field);
    }

    protected function copyUnitArchives($eid)
    {
        $DB = DB::singleton(dsn());
        $bid = ACMS_RAM::entryBlog($eid);
        $SQL = SQL::newSelect('column');
        $SQL->addWhereOpr('column_entry_id', $eid);
        $SQL->addWhereOpr('column_blog_id', $bid);
        $q = $SQL->get(dsn());
        if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
            do {
                $type = detectUnitTypeSpecifier($row['column_type']);
                switch ($type) {
                    case 'image':
                        $this->copyImageUnit($row);
                        break;
                    case 'file':
                        $this->copyFileUnit($row);
                        break;
                    case 'custom':
                        $this->copyCustomUnit($row);
                        break;
                }
            } while ($row = $DB->fetch($q));
        }
    }

    /**
     * @param array $row
     */
    protected function copyImageUnit($row)
    {
        $oldAry = explodeUnitData($row['column_field_2']);
        foreach ($oldAry as $old) {
            $path = ARCHIVES_DIR . $old;
            $large = otherSizeImagePath($path, 'large');
            $tiny = otherSizeImagePath($path, 'tiny');
            $square = otherSizeImagePath($path, 'square');

            $this->allCopy($path);
            $this->allCopy($large);
            $this->allCopy($tiny);
            $this->allCopy($square);
        }
    }

    /**
     * @param array $row
     */
    protected function copyFileUnit($row)
    {
        $oldAry = explodeUnitData($row['column_field_2']);
        foreach ($oldAry as $old) {
            $this->allCopy(ARCHIVES_DIR . $old);
        }
    }

    /**
     * @param array $row
     */
    protected function copyCustomUnit($row)
    {
        $Field = acmsUnserialize($row['column_field_6']);
        $this->fieldDupe($Field);
    }

    /**
     * @param \Field $Field
     */
    protected function fieldDupe($Field)
    {
        foreach ($Field->listFields() as $fd) {
            if (preg_match('/(.*?)@path$/', $fd, $match)) {
                $_fd = $match[1];

                // カスタムフィールドグループ対応
                $ary_path = $Field->getArray($_fd . '@path');
                if (is_array($ary_path) && count($ary_path) > 0) {
                    $fieldIndex = 0;
                    foreach ($ary_path as $path) {
                        if (
                            1
                            and Storage::isFile(ARCHIVES_DIR . $path)
                            and preg_match('@^(.*?)([^/]+)(\.[^.]+)$@', $path, $match)
                        ) {
                            foreach (
                                array(
                                         '' => '@path',
                                         'large-' => '@largePath',
                                         'tiny-' => '@tinyPath',
                                         'square-' => '@squarePath',
                                     ) as $pfx => $name
                            ) {
                                if (
                                    1
                                    and $path = $Field->get($_fd . $name, null, $fieldIndex)
                                    and Storage::isFile(ARCHIVES_DIR . $path)
                                ) {
                                    $this->allCopy(ARCHIVES_DIR . $path);
                                }
                            }
                            $fieldIndex++;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $path
     */
    protected function allCopy($path)
    {
        foreach ($this->destinationPaths as $destinationPath) {
            Storage::makeDirectory(dirname($destinationPath . $path));
            Storage::copy($path, $destinationPath . $path);

            if ($dirname = dirname($path)) {
                $dirname .= '/';
            }
            $basename = Storage::mbBasename($path);
            $files = glob($dirname . '*-' . $basename);
            if (is_array($files)) {
                foreach ($files as $file) {
                    Storage::copy($file, $destinationPath . $file);
                }
            }
        }
    }
}
