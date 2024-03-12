<?php

namespace Acms\Services\Database;

use DB;
use AcmsLogger;

class Replication
{
    /**
     * @var \Acms\Services\Database\Engine
     */
    protected $db;

    /**
     * @var string
     */
    protected $dbName;

    /**
     * @var array
     */
    protected $dsn;

    public function __construct($dsn = null)
    {
        if (empty($dsn)) {
            $this->dsn = dsn();
        }
        $this->dbName = $this->dsn['name'];
        $this->db = DB::singleton($this->dsn);
    }

    /**
     * 全テーブルの取得
     *
     * @return array
     */
    public function getTableList()
    {
        $sql = 'SHOW TABLES FROM `' . $this->dbName . '`';
        $tables = DB::query($sql, 'all');

        $list = array();
        foreach ($tables as $key => $table) {
            array_push($list, strtolower(reset($table)));
        }

        return $list;
    }

    /**
     * 全テーブルの削除
     *
     * @return void
     */
    public function dropAllTables()
    {
        $list = array();
        foreach ($this->getTableList() as $table) {
            $table = strtolower($table);
            array_push($list, '`' . $table . '`');
        }
        $tables_str = implode(', ', $list);
        $sql = 'DROP TABLE ' . $tables_str;
        $sql2 = 'DROP TABLE ' . strtoupper($tables_str);

        DB::query($sql, 'exec');
        DB::query($sql2, 'exec');
    }

    /**
     * 全テーブルのリネーム
     *
     * @return void
     */
    public function renameAllTable()
    {
        $list = array();
        foreach ($this->getTableList() as $table) {
            $table = strtolower($table);
            if (!preg_match('/^backup_acms_.*/', $table) and preg_match('/^' . DB_PREFIX . '.*/', $table)) {
                array_push($list, $table . ' TO backup_acms_' . $table);
            }
        }
        $tables_str = implode(', ', $list);
        $sql = 'RENAME TABLE ' . $tables_str;

        DB::query($sql, 'exec');
    }

    /**
     * 一時テーブルの削除
     *
     * @return void
     */
    public function dropCashTable()
    {
        $list = array();
        foreach ($this->getTableList() as $table) {
            $table = strtolower($table);
            if (preg_match('/^backup_acms_.*/', $table)) {
                array_push($list, '`' . $table . '`');
            }
        }
        $tables_str = implode(', ', $list);
        if (!empty($tables_str)) {
            $sql = 'DROP TABLE ' . $tables_str;
            $sql2 = 'DROP TABLE ' . strtoupper($tables_str);

            try {
                DB::query($sql, 'exec');
            } catch (\Exception $e) {
                AcmsLogger::notice($e->getMessage());
            }

            try {
                DB::query($sql2, 'exec');
            } catch (\Exception $e) {
                AcmsLogger::notice($e->getMessage());
            }
        }
    }

    /**
     * テーブル作成クエリの組み立て
     *
     * @return string
     */
    public function buildCreateTableSql()
    {
        $master = '';
        $list = array();
        foreach ($this->getTableList() as $table) {
            $table = strtolower($table);
            if (!preg_match('/^backup_acms_.*/', $table) and preg_match('/^' . DB_PREFIX . '.*/', $table)) {
                array_push($list, $table);
            }
        }

        foreach ($list as $key => $row) {
            $sql = 'SHOW CREATE TABLE ' . $row;
            $create = DB::query($sql, 'all');
            foreach ($create as $row) {
                $create_sql = $row['Create Table'];
                $create_sql = str_replace(array("\r\n", "\n", "\r"), '', $create_sql);
                $master .= $create_sql . ';' . PHP_EOL;
            }
        }

        return $master;
    }

    /**
     * データ投入sqlの組み立て
     *
     * @param string $table
     * @param resource $handle
     *
     * @return void
     */
    public function buildInsertSql($table, &$handle)
    {
        if (preg_match('/^backup_acms_.*/', $table)) {
            return;
        }
        $db = DB::singleton(dsn());
        $columnsList = array();
        $columnsType = array();

        $columns = $db->query('SHOW COLUMNS FROM `' . $table . '`', 'all');
        foreach ($columns as $row) {
            $name = $row['Field'];
            array_push($columnsList, $name);
            $columnsType[$name] = $row['Type'];
        }
        $q = "SELECT * FROM $table";
        $db->query($q, 'fetch', false);

        while ($row = $db->fetch($q)) {
            $masterQuery = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $columnsList) . '`) VALUES ';
            $masterQuery .= '(';
            $j = 0;
            foreach ($columnsType as $name => $type) {
                $type = strtolower($type);
                if ($j !== 0) {
                    $masterQuery .= ', ';
                }
                $value = $row[$name];
                if ($value === null) {
                    $masterQuery .= 'NULL';
                } else {
                    if (preg_match('/(blob|binary|point|geometry)/', $type) || false === detectEncode($value)) {
                        $value = 'X\'' . bin2hex($value) . '\'';
                    } else {
                        $value = DB::quote($value);
                    }
                    $masterQuery .= $value;
                }
                $j++;
            }
            $masterQuery .= ');' . PHP_EOL;
            $masterQuery = preg_replace('/' . DB_PREFIX . '/', 'DB_PREFIX_STR_', $masterQuery);
            if ('UTF-8' <> DB_CHARSET) {
                $val = @mb_convert_encoding($masterQuery, "UTF-8", DB_CHARSET);
                if ($masterQuery === mb_convert_encoding($val, DB_CHARSET, 'UTF-8')) {
                    $masterQuery = $val;
                }
            }
            fwrite($handle, $masterQuery);
        }
    }

    /**
     * ドメインの書き換え
     *
     * @param string $new_domain
     * @param string $name
     *
     * @return void
     */
    public function rewriteDomain($new_domain, $name)
    {
        $sql = 'UPDATE ' . $name . ' SET blog_domain=' . DB::quote($new_domain);
        DB::query($sql, 'exec');
    }

    /**
     * @throws \RuntimeException
     */
    public function authorityValidation()
    {
        $table = 'TEMP_' . date('yMd_His');
        $new_table = 'R_' . $table;

        if (!DB::query('CREATE TABLE `' . $table . '` (test VARCHAR(1))', 'exec')) {
            throw new \RuntimeException('CREATE TABLE権限がありません。 ' . implode(' ', DB::errorInfo()));
        }

        if (!DB::query('RENAME TABLE `' . $table . '` TO `' . $new_table . '`', 'exec')) {
            throw new \RuntimeException('RENAME TABLEする権限がありません。 ' . implode(' ', DB::errorInfo()));
        }

        if (!DB::query('DROP TABLE `' . $new_table . '`', 'exec')) {
            throw new \RuntimeException('DROP TABLEする権限がありません。 ' . implode(' ', DB::errorInfo()));
        }

        if (!DB::query('SHOW TABLES FROM `' . DB_NAME . '`', 'exec')) {
            throw new \RuntimeException('SHOW TABLESする権限がありません。 ' . implode(' ', DB::errorInfo()));
        }
    }
}
