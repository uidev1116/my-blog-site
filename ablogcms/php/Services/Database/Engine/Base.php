<?php

namespace Acms\Services\Database\Engine;

use ACMS_Hook;

abstract class Base
{
    /**
     * @var mixed
     */
    protected $connection;

    /**
     * @var array
     */
    protected $fetch;

    /**
     * @var array
     */
    protected $dsn;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var int
     */
    protected $affectedRows;

    /**
     * @var int
     */
    protected $columnCount;

    /**
     * new static()でインスタンスを作成するようにするため、constructorを上書きできないようにする
     */
    final public function __construct()
    {
    }

    /**
     * connect mysql server
     *
     * @param array $dsn
     */
    abstract public function connect($dsn);

    /**
     * reconnect mysql server
     *
     * @param $dsn
     * @return void
     */
    abstract public function reconnect($dsn);

    /**
     * Get SQL Server Version
     *
     * @return string
     */
    abstract public function getVersion();

    /**
     * SQL文を指定してmodeに応じたDB操作結果を返す<br>
     * 'row'    => 最初の行の連想配列を返す(array)<br>
     * 'all'    => すべての行を連想配列で返す(array)<br>
     * 'exec'   => mysql_query()の結果を返す(resource)<br>
     * 'fetch'  => fetchキャッシュを生成する(bool)<br>
     * 'one'    => 最初の行の最初のfieldを返す<br>
     * 'seq'    => insert,update,deleteされた件数を返す(int)
     *
     * @param string $sql
     * @param string $mode
     * @return array|bool|resource|int|string
     */
    abstract public function query($sql, $mode = 'row');

    /**
     * sql文を指定して1行ずつfetchされた値を返す
     * $DB->query($SQL->get(dsn()), 'fetch');<br>
     * while ( $row = $DB->fetch($q) ) {<br>
     *     $Config->addField($row['config_key'], $row['config_value']);<br>
     * }
     *
     * @param string $sql
     * @return array | bool
     */
    abstract public function fetch($sql = null, $reset = false);

    /**
     * query()の結果を返す
     *
     * @param string $sql
     * @param mixed $response
     * @return mixed
     */
    abstract protected function execMode($sql, $response);

    /**
     * insert,update,deleteされた件数を返す
     *
     * @param string $sql
     * @param mixed $response
     * @return int
     */
    abstract protected function seqMode($sql, $response);

    /**
     * すべての行を連想配列で返す
     *
     * @param string $sql
     * @param mixed $response
     * @return array
     */
    abstract protected function allMode($sql, $response);

    /**
     * 最初の行を配列で返す
     *
     * @param string $sql
     * @param mixed $response
     * @return array
     */
    abstract protected function listMode($sql, $response);

    /**
     * 最初の行の最初のcolumnの値を返す
     *
     * @param string $sql
     * @param mixed $response
     * @return string
     */
    abstract protected function oneMode($sql, $response);

    /**
     * 最初の行の連想配列を返す
     *
     * @param string $sql
     * @param mixed $response
     * @return array
     */
    abstract protected function rowMode($sql, $response);

    /**
     * Returns metadata for a column in a result set
     *
     * @param int $column
     *
     * @return array
     */
    abstract public function columnMeta($column);

    /**
     * データベースサーバーへの接続チェック
     *
     * @return bool
     */
    abstract public function checkConnection($dsn);

    /**
     * データベースへの接続チェック
     *
     * @return bool
     */
    abstract public function checkConnectDatabase($dsn);

    /**
     * クエリ書き換え用Hook
     *
     * @param string $sql
     */
    public function hook(&$sql)
    {
        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('query', [&$sql]);
        }
    }

    /**
     * クエリ用の文字列をクオートする
     *
     * @param string $string
     * @return string
     */
    public static function quote($string)
    {
        throw new \RuntimeException('Database Engine does not defined quote method.');
    }

    /**
     * DB識別子(dsn)を指定してDBオブジェクトを返す
     *
     * @static
     * @param array $dsn
     * @return static
     */
    public static function singleton($dsn = null)
    {
        static $connections = [];

        $id = sha1(serialize($dsn));
        if (!isset($connections[$id])) {
            $obj = new static();
            $obj->connect($dsn);
            $connections[$id] = $obj;
        }

        return $connections[$id];
    }

    /**
     * バージョンによって、サブクエリを使用するか分離するかを判断
     *
     * @param \SQL $query
     * @param bool $subquery
     * @return \SQL|null|array|bool
     */
    public function subQuery($query, $subquery = false)
    {
        $version = $this->getVersion();
        if (version_compare($this->getVersion(), '5.6.0', '>=')) {
            return $query;
        }
        if (strpos(strtolower($version), 'mariadb') !== false) {
            return $query;
        }
        if ($subquery) {
            $DB = self::singleton(dsn());
            $Amount = new \SQL_Select($query);
            $Amount->setSelect('*', 'amount', null, 'COUNT');
            $q = $Amount->get(dsn());
            $amount = intval($DB->query($q, 'one'));

            if ($amount > 300) {
                return $query;
            }
        }
        return $this->query($query->get(dsn()), 'list');
    }

    /**
     * @param array $dsn
     * @return static
     */
    public function persistent($dsn = null)
    {
        return self::singleton($dsn);
    }

    /**
     * エラー情報の取得
     *
     * @return array
     */
    public function errorInfo()
    {
        return $this->connection->errorInfo();
    }

    /**
     * get error code
     *
     * @return mixed
     */
    public function errorCode()
    {
        return $this->connection->errorCode();
    }

    /**
     * @param string $sql
     * @param int $time
     *
     * @return array|int|void
     */
    public static function time($sql = null, $time = null)
    {
        static $arySql = [];
        static $aryTime = [];

        if (is_int($sql)) {
            $res = [];
            foreach ($aryTime as $i => $timeValue) {
                $res[strval($timeValue)] = $arySql[$i];
            }
            krsort($res);
            $_res = $res;
            $res = [];
            $i = 0;
            foreach ($_res as $key => $val) {
                $res[$key] = $val;
                if (++$i >= $sql) {
                    break;
                }
            }
            return $res;
        }

        if (is_null($sql)) {
            return array_sum($aryTime);
        }

        $arySql[] = $sql;
        $aryTime[] = $time;
    }

    /**
     * @param string $sql
     * @return bool
     */
    public function isFetched($sql = null)
    {
        $this->hook($sql);
        $id = !empty($sql) ? sha1($sql) : '';

        return isset($this->fetch[$id]);
    }

    /**
     * Returns the number of rows affected by the last SQL statement
     *
     * @return int
     */
    public function affected_rows()
    {
        return ($this->affectedRows > 0) ? $this->affectedRows : 0;
    }

    /**
     * Returns the number of columns in the result set
     *
     * @return int
     */
    public function columnCount()
    {
        return ($this->columnCount > 0) ? $this->columnCount : 0;
    }

    /**
     * get connection
     *
     * @return mixed
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * get charset
     *
     * @return string
     */
    public function charset()
    {
        return $this->dsn['charset'];
    }

    /**
     * optimize table
     *
     * return void
     */
    public function optimizeTable()
    {
        $this->query('OPTIMIZE TABLE `' . DB_PREFIX . 'cache`', 'exec');
    }

    /**
     * fetchキャッシュを生成する
     *
     * @param string $sql
     * @param mixed $response
     * @return bool
     */
    protected function fetchMode($sql, $response)
    {
        $this->hook($sql);
        $this->fetch[sha1($sql)] = &$response;

        return true;
    }

    /**
     * 存在しないモードで実行された場合、rowモードを実行する
     *
     * @param string $sql
     * @param mixed $response
     * @return array
     */
    protected function etcMode($sql, $response)
    {
        return $this->rowMode($sql, $response);
    }

    /**
     * get charset
     *
     * @param array $dsn
     * @return string
     */
    protected function getCharset($dsn)
    {
        $charset = isset($dsn['charset']) ? $dsn['charset'] : 'UTF-8';

        if (preg_match('@^[shiftj]+$@i', $charset)) {
            $charset = 'sjis';
        } elseif (preg_match('@^[eucjp_\-]+$@i', $charset)) {
            $charset = 'ujis';
        } else {
            $charset = 'utf8';
        }
        if (defined('DB_CONNECTION_CHARSET') && !!DB_CONNECTION_CHARSET) {
            $charset = DB_CONNECTION_CHARSET;
        }
        return $charset;
    }

    /**
     * sav processing time
     *
     * @param string $sql
     * @param float $time
     * @return void
     */
    protected function saveProcessingTime($sql, $time)
    {
        if (empty($this->dsn['debug'])) {
            return;
        }
        $this->hook($sql);
        if (isBenchMarkMode() && $time > DB_SLOW_QUERY_TIME) {
            global $bench_slow_query;
            $bench_slow_query[] = [
                'time' => $time,
                'query' => nl2br($sql),
            ];
        }
        self::time($sql, $time);
    }
}
