<?php

namespace Acms\Services\Update\Database;

use DB;

class DatabaseInfo
{
    /**
     * DB接続情報
     *
     * @var array
     */
    protected $dsn;

    /**
     * DatabaseInfo constructor.
     *
     * @param $dsn array
     */
    public function __construct($dsn)
    {
        $this->dsn = $dsn;
    }

    /**
     * テーブル一覧の取得
     *
     * @return array
     */
    public function getTables()
    {
        $DB = DB::singleton($this->dsn);
        $q = "SHOW TABLES FROM `" . $this->dsn['name'] . "` LIKE '" . $this->dsn['prefix'] . "%'";
        $DB->query($q, 'fetch');

        $tables = [];
        while ($tb = $DB->fetch($q)) {
            $tables[] = implode($tb);
        }
        return $tables;
    }

    /**
     * テーブルのカラム一覧の取得
     *
     * @param $table string
     * @return array
     */
    public function getColumns($table)
    {
        $DB = DB::singleton($this->dsn);
        $q = "SHOW COLUMNS FROM `{$table}`";
        $DB->query($q, 'fetch');

        $columns = [];
        while ($fd = $DB->fetch($q)) {
            $columns[$fd['Field']] = $fd;
        }
        return $columns;
    }

    /**
     * テーブルのインデックスの取得
     *
     * @param $table string
     * @return array
     */
    public function getIndex($table)
    {
        $DB = DB::singleton($this->dsn);
        $q = "SHOW INDEX FROM `{$table}`";
        $DB->query($q, 'fetch');

        $index = [];
        while ($fd = $DB->fetch($q)) {
            $index[] = $fd['Key_name'];
        }
        $index = array_values(array_unique($index));
        return $index;
    }

    /**
     * カラムのリネーム
     *
     * @param string $table
     * @param string $left
     * @param array $def
     * @param string $right
     */
    public function rename($table, $left, $def, $right)
    {
        $this->alterTable('rename', $table, $left, $def, $right);
    }

    /**
     * テーブルのEngineを変更
     *
     * @param string $table
     * @param string $engine
     */
    public function changeEngine($table, $engine)
    {
        $DB = DB::singleton($this->dsn);
        $sql = "SELECT ENGINE FROM information_schema.tables WHERE table_schema = '" . $this->dsn['name'] . "' AND table_name = '" . $table . "'";
        $current = $DB->query($sql, 'one');

        if ($current === $engine) {
            return;
        }
        $this->alterTable('engine', $table, $engine);
    }

    /**
     * カラムの追加
     *
     * @param string $table
     * @param string $left
     * @param array $def
     * @param string $after
     */
    public function add($table, $left, $def, $after)
    {
        $this->alterTable('add', $table, $left, $def, $after);
    }

    /**
     * カラムの変更
     *
     * @param string $table
     * @param string $left
     * @param array $def
     */
    public function change($table, $left, $def)
    {
        $this->alterTable('change', $table, $left, $def);
    }

    /**
     * 現在のインデックスを取得
     *
     * @param string $table
     * @return array
     */
    public function showIndex($table)
    {
        $DB = DB::singleton($this->dsn);
        $q = "SHOW INDEX FROM `$table`";
        $DB->query($q, 'fetch');

        $fds = [];
        while ($fd = $DB->fetch($q)) {
            $fds[] = $fd;
        }

        return $fds;
    }

    /**
     * _alterTable カラム定義の変更を適用する
     *
     * @param string $method
     * @param string $tb
     * @param string $left
     * @param array|null $def カラム定義
     * @param string $right
     * @return void
     */
    protected function alterTable($method, $tb, $left, $def = null, $right = null)
    {
        $q = "ALTER TABLE `$tb`";

        $def['Null'] = ($def['Null'] == 'NO') ? 'NOT NULL' : 'NULL';
        $def['Default'] = !empty($def['Default']) ? "default '" . $def['Default']  . "'" : null;
        $def['Extra'] = isset($def['Extra']) ? ' ' . $def['Extra'] : '';

        switch ($method) {
            case 'add':
                $q .= " ADD";
                $q .= " `" . $left . "` " . $def['Type'] . " " . $def['Null'] . " " . $def['Default'] . $def['Extra'] . " AFTER " . " `" . $right . "`";
                break;
            case 'change':
                // カラムのサイズ変更で現行サイズより小さい場合は処理をスキップ
                if (preg_match('/^[a-z]+\((\d+)\)/', $def['Type'], $match)) {
                    $cq = "SHOW COLUMNS FROM " . $tb . " LIKE '" . $left . "'";
                    $DB = DB::singleton($this->dsn);
                    $DB->query($cq, 'fetch');
                    $size = $match[1];

                    if ($row = $DB->fetch($cq)) {
                        $type = $row['Type'];
                        if (preg_match('/^[a-z]+\((\d+)\)/', $type, $match)) {
                            $csize = $match[1];
                            if (intval($size) < intval($csize)) {
                                break;
                            }
                        }
                    }
                }
                $q .= " CHANGE";
                $q .= " `" . $left . "` `" . $left . "` " . $def['Type'] . " " . $def['Null'] . " " . $def['Default'] . $def['Extra'];
                break;
            case 'rename':
                $q .= " CHANGE";
                $q .= " `" . $left . "` `" . $right . "` " . $def['Type'] . " " . $def['Null'] . " " . $def['Default'] . $def['Extra'];
                break;
            case 'engine':
                $q .= " ENGINE=";
                $q .= $left;
                break;
            case 'drop':
                $q .= " DROP";
                $q .= " `" . $left . "`";
        }
        $DB = DB::singleton($this->dsn);
        $DB->query($q, 'exec');
    }

    /**
     * テーブルを作成する
     *
     * @param array $tables
     * @param array|null $idx
     *
     * @throws \RuntimeException
     */
    public function createTables($tables, $idx = null, $define = [])
    {
        foreach ($tables as $tb) {
            $def = $define[$tb];

            $q = "CREATE TABLE {$tb} ( \r\n";
            foreach ($def as $row) {
                $row['Null'] = (isset($row['Null']) && $row['Null'] == 'NO') ? 'NOT NULL' : 'NULL';
                $row['Default'] = !empty($row['Default']) ? "default '" . $row['Default'] . "'" : null;

                // Example: field_name var_type(11) NOT NULL default HOGEHOGE,\r\n
                $q .= $row['Field'] . ' ' . $row['Type'] . ' ' . $row['Null'] . ' ' . $row['Default']  .  ' ' . $row['Extra'] . ",\r\n";
            }

            /**
             * if $idx is exists Generate KEYs
             */
            if (is_array($idx) && !empty($idx) && isset($idx[$tb])) {
                $keys = $idx[$tb];
                if (is_array($keys) && !empty($keys)) {
                    foreach ($keys as $key) {
                        $q .= $key . ",\r\n";
                    }
                }
            }
            $q = preg_replace('@,(\r\n)$@', '$1', $q);
            if (preg_match('/(fulltext|geo)$/', $tb)) {
                $q .= ") ENGINE=MyISAM;";
            } else {
                $q .= ") ENGINE=InnoDB;";
            }

            $DB = DB::singleton($this->dsn);
            $isSuccess = $DB->query($q, 'exec');
            if ($isSuccess === false) {
                throw new \RuntimeException('「' . $tb . '」' . 'テーブルの作成に失敗しました。');
            }
        }
    }
}
