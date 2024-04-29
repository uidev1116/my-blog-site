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
        $this->setTables([
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
            'blog',
        ]);
        $dsn = dsn();
        $this->prefix = $dsn['prefix'];
    }

    public function export($fp, $bid)
    {
        $queryList = [];
        foreach ($this->tables as $table) {
            $sql = SQL::newSelect($table);
            $sql->addWhereOpr($table . '_blog_id', $bid);
            $method = 'fixQuery' . ucfirst($table);
            if (is_callable([$this, $method])) {
                $sql = call_user_func_array([$this, $method], [$sql, $bid]);
            }
            $q = $sql->get(dsn());
            $queryList[$table] = $q;
        }
        $this->dumpYaml($fp, $queryList);
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
    private function fixFulltext(&$record)
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
    private function fixSchedule(&$record)
    {
        if (
            1
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
     * @param \SQL_Select $SQL
     * @param int $bid
     * @return mixed
     */
    private function fixQueryColumn($SQL, $bid = 0)
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
     * @param \SQL_Select $SQL
     * @param int $bid
     * @return mixed
     */
    private function fixQueryComment($SQL, $bid = 0)
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
     * @param \SQL_Select $SQL
     * @param int $bid
     * @return mixed
     */
    private function fixQueryEntry($SQL, $bid = 0)
    {
        $SQL->addWhereOpr('entry_status', 'trash', '<>');

        return $SQL;
    }

    /**
     * フィールドデータからゴミ箱のデータを除外
     *
     * @param \SQL_Select $SQL
     * @param int $bid
     * @return mixed
     */
    private function fixQueryField($SQL, $bid = 0)
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
     * @param \SQL_Select $SQL
     * @param int $bid
     * @return mixed
     */
    private function fixQueryFulltext($SQL, $bid = 0)
    {
        $columns = DB::query('SHOW COLUMNS FROM ' . $this->prefix . 'fulltext', 'all');
        foreach ($columns as $column) {
            $SQL->addSelect($column['Field']);
        }
        $SQL->addLeftJoin('entry', 'fulltext_eid', 'entry_id');
        $SQL->addWhereOpr('entry_status', 'trash', '<>');

        return $SQL;
    }

    /**
     * ブログデータを部分的エクスポートに修正
     *
     * @param \SQL_Select $SQL
     * @param int $bid
     * @return mixed
     */
    private function fixQueryBlog($SQL, $bid = 0)
    {
        $SQL = SQL::newSelect('blog');
        $SQL->addSelect('blog_config_set_id');
        $SQL->addSelect('blog_config_set_scope');
        $SQL->addSelect('blog_theme_set_id');
        $SQL->addSelect('blog_theme_set_scope');
        $SQL->addSelect('blog_editor_set_id');
        $SQL->addSelect('blog_editor_set_scope');
        $SQL->addWhereOpr('blog_id', $bid);

        return $SQL;
    }
}
