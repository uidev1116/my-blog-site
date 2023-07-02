<?php

namespace Acms\Services\Blog;

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

    public function __construct()
    {
        $this->setTables(array(
            'category',
            'column',
            'config',
            'comment',
            'config_set',
            'dashboard',
            'entry',
            'field',
            'form',
            'media',
            'media_tag',
            'module',
            'rule',
            'tag',
            'schedule',
            'layout_grid',
        ));
        $dsn = dsn();
        $this->prefix = $dsn['prefix'];
    }

    public function export($fp, $bid)
    {
        $queryList = array();
        foreach ($this->tables as $table) {
            $sql = SQL::newSelect($table);
            $sql->addWhereOpr($table . '_blog_id', $bid);
            $method = 'fixQuery' . ucfirst($table);
            if (is_callable(array($this, $method))) {
                $sql = call_user_func_array(array($this, $method), array($sql));
            }
            $q = $sql->get(dsn());
            $queryList[$table] = $q;
        }
        $this->dumpYaml($fp, $queryList);
    }

    /**
     * fix data
     *
     * @param &array $records
     * @param string $table
     *
     * @return void
     */
    protected function fix(& $record, $table)
    {
        if ($table === 'column' && 'text' === $record['column_type']) {
            $txt = $record['column_field_1'];
            $record['column_field_1'] = $this->fixNextLine($txt);
        }
        if ($table === 'schedule') {
            $this->fixSchedule($record);
        }
        if ($table === 'fulltext') {
            $this->fixFulltext($record);
        }
    }

    /**
     * ignore user fulltext
     *
     * @param $record
     */
    private function fixFulltext(& $record)
    {
        if (!empty($record['fulltext_uid'])) {
            $record = false;
        }
        $txt = $record['fulltext_value'];
        $record['fulltext_value'] = $this->fixNextLine($txt);
    }

    /**
     * ignore schedule data without base set
     *
     * @param $record
     */
    private function fixSchedule(& $record)
    {
        if (1
            and $record['schedule_year'] == 0000
            and $record['schedule_month'] == 00
        ) {
            // no touch
        } else {
            $record = false;
        }
    }

    /**
     * ユニットデータからゴミ箱のデータを除外
     *
     * @param $SQL
     * @return mixed
     */
    private function fixQueryColumn($SQL)
    {
        $columns = DB::query('SHOW COLUMNS FROM ' . $this->prefix . 'column', 'all');
        foreach ($columns as $column) {
            $SQL->addSelect($column['Field']);
        }
        $SQL->addLeftJoin('entry', 'column_entry_id', 'entry_id');
        $SQL->addWhereOpr('entry_status', 'trash', '<>');

        return $SQL;
    }

    /**
     * コメントデータからゴミ箱のデータを除外
     *
     * @param $SQL
     * @return mixed
     */
    private function fixQueryComment($SQL)
    {
        $columns = DB::query('SHOW COLUMNS FROM ' . $this->prefix . 'comment', 'all');
        foreach ($columns as $column) {
            $SQL->addSelect($column['Field']);
        }
        $SQL->addLeftJoin('entry', 'comment_entry_id', 'entry_id');
        $SQL->addWhereOpr('entry_status', 'trash', '<>');

        return $SQL;
    }

    /**
     * エントリーデータからゴミ箱のデータを除外
     *
     * @param $SQL
     * @return mixed
     */
    private function fixQueryEntry($SQL)
    {
        $SQL->addWhereOpr('entry_status', 'trash', '<>');

        return $SQL;
    }

    /**
     * フィールドデータからゴミ箱のデータを除外
     *
     * @param $SQL
     * @return mixed
     */
    private function fixQueryField($SQL)
    {
        $columns = DB::query('SHOW COLUMNS FROM ' . $this->prefix . 'field', 'all');
        foreach ($columns as $column) {
            $SQL->addSelect($column['Field']);
        }
        $SQL->addLeftJoin('entry', 'field_eid', 'entry_id');

        $SUB = SQL::newWhere();
        $SUB->addWhereOpr('entry_status', 'trash', '<>', 'OR');
        $SUB->addWhereOpr('field_eid', null, '=', 'OR');
        $SQL->addWhere($SUB);

        return $SQL;
    }

    /**
     * フルテキストデータからゴミ箱のデータを除外
     *
     * @param $SQL
     * @return mixed
     */
    private function fixQueryFulltext($SQL)
    {
        $columns = DB::query('SHOW COLUMNS FROM ' . $this->prefix . 'fulltext', 'all');
        foreach ($columns as $column) {
            $SQL->addSelect($column['Field']);
        }
        $SQL->addLeftJoin('entry', 'fulltext_eid', 'entry_id');
        $SQL->addWhereOpr('entry_status', 'trash', '<>');

        return $SQL;
    }
}
