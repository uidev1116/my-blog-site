<?php

namespace Acms\Services\Blog;

use SQL;
use DB;
use Symfony\Component\Yaml\Yaml;

class Export
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
        $db = DB::singleton(dsn());
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
        foreach ($queryList as $table => $q) {
            $db->query($q, 'fetch', false);
            fwrite($fp, "$table:\n");
            while ($row = $db->fetch($q)) {
                $this->fix($row, $table);
                $record = Yaml::dump(array('dummy' => $row), 1);
                if ($record) {
                    $record = $this->fixYaml($record);
                    fwrite($fp, str_replace('dummy:', '    -', $record));
                }
            }
        }
    }

    /**
     * set export tables
     *
     * @param array $tables
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function setTables($tables = array())
    {
        if (!is_array($tables)) {
            throw new \RuntimeException('Not specified tables.');
        }
        $this->tables = $tables;
    }

    /**
     * @param string $txt
     * @return string
     */
    private function fixYaml($txt)
    {
        return preg_replace('@(001)/(.*)\.([^\.]{2,6})@ui', '001/$2.$3', $txt, -1);
    }

    /**
     * fix data
     *
     * @param &array $records
     * @param string $table
     *
     * @return void
     */
    private function fix(& $record, $table)
    {
        if ($table === 'column') {
            $this->fixHalfSpace($record);
        }
        if ($table === 'schedule') {
            $this->fixSchedule($record);
        }
        if ($table === 'fulltext') {
            $this->fixFulltext($record);
        }
    }

    /**
     * escape single byte spaces of row's header
     *
     * @param $record
     *
     * @return void
     */
    private function fixHalfSpace(& $record)
    {
        if ('text' !== $record['column_type']) {
            return;
        }
        $txt = $record['column_field_1'];
        /*
         * carrige returns \r and \r\n
         * Paragraph Separator (U+2028)
         * Line Separator (U+2029)
         * Next Line (NEL) (U+0085)
         */
        $record['column_field_1'] = preg_replace('/(\xe2\x80[\xa8-\xa9]|\xc2\x85|\r\n|\r)/', "\n", $txt);
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
        $record['fulltext_value'] = preg_replace('/(\xe2\x80[\xa8-\xa9]|\xc2\x85|\r\n|\r)/', "\n", $txt);
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
