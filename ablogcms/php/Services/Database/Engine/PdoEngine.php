<?php

namespace Acms\Services\Database\Engine;

use App;
use PDO;
use AcmsLogger;
use PDOException;

/**
 * Class Pdo
 * @package Acms\Services\Database\Engine
 */
class PdoEngine extends Base
{
    /**
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * @var bool
     */
    protected $throwException = false;

    /**
     * connect mysql server
     *
     * @param array $dsn
     */
    public function connect($dsn)
    {
        $connect_str = 'mysql:host=';
        $host = explode(':', $dsn['host']);
        $connect_str .= $host[0] . ';';
        if (!empty($dsn['name'])) {
            $connect_str .= 'dbname=' . $dsn['name'] . ';';
        }
        if (!empty($dsn['port']) || isset($host[1])) {
            $port = empty($dsn['port']) ? $host[1] : $dsn['port'];
            $connect_str .= 'port=' . $port . ';';
        }

        $options = array();

        $connect_str .= 'charset=' . $this->getCharset($dsn);

        try {
            $this->connection = new PDO(
                $connect_str,
                $dsn['user'],
                $dsn['pass'],
                $options
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw $e;
        }

        $charset = isset($dsn['charset']) ? $dsn['charset'] : 'UTF-8';
        $this->debug = !empty($dsn['debug']);
        $this->dsn = array(
            'type' => isset($dsn['type']) ? $dsn['type'] : null,
            'debug' => $this->debug,
            'charset' => $charset,
        );
    }

    /**
     * reconnect mysql server
     *
     * @param $dsn
     * @return void
     */
    public function reconnect($dsn)
    {
        $this->connection = null;
        $this->statement = null;
        $this->connect($dsn);
    }

    /**
     * データベースサーバーへの接続チェック
     *
     * @return bool
     */
    public function checkConnection($dsn)
    {
        try {
            $dsn['name'] = '';
            $this->connect($dsn);
        } catch (PDOException $e) {
            return false;
        }

        return true;
    }

    /**
     * データベースへの接続チェック
     *
     * @return bool
     */
    public function checkConnectDatabase($dsn)
    {
        if (empty($dsn['name'])) {
            return false;
        }
        try {
            $this->connect($dsn);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * 例外をスローするか設定
     *
     * @param bool $throw
     */
    public function setThrowException($throw = true)
    {
        $this->throwException = $throw;
    }

    /**
     * クエリ用の文字列をクオートする
     *
     * @param string $string
     * @return string
     */
    public static function quote($string)
    {
        $DB = self::singleton(dsn());
        return $DB->connection->quote($string);
    }

    /**
     * Get SQL Server Version
     *
     * @return string
     */
    public function getVersion()
    {
        static $version = false;
        if ($version) {
            return $version;
        }
        $db = self::singleton(dsn());
        $version = $db->query('select version()', 'one');
        return $version;
    }

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
     * @param boolean $buffered
     * @param boolean $auditLog
     * @return array|bool|resource|int|null
     *
     * @throws \ErrorException
     */
    public function query($sql, $mode = 'row', $buffered = true, $auditLog = true)
    {
        global $query_result_count;
        $query_result_count++;

        try {
            $this->hook($sql);
            $start_time = microtime(true);
            if ($buffered === false) {
                $this->connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            }
            $res = $this->connection->query($sql);
            $exe_time = sprintf('%0.6f', microtime(true) - $start_time);
            $this->saveProcessingTime($sql, $exe_time);
            $this->affectedRows = $res ? $res->rowCount() : 0;
            $this->columnCount = $res ? $res->columnCount() : 0;
            $this->statement = $res;

            $method = strtolower($mode) . 'Mode';
            if (method_exists($this, $method)) {
                $result = $this->{$method}($sql, $res); // @phpstan-ignore-line
            } else {
                $result = $this->etcMode($sql, $res);
            }
            return $result;
        } catch (PDOException $e) {
            if ($auditLog) {
                AcmsLogger::debug($e->getMessage(), [
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'sql' => $sql,
                ]);
            }
            if ($this->debug) {
                $code = intval($e->getCode());
                $exception = new \ErrorException($e->getMessage(), $code, E_USER_WARNING, $e->getFile(), $e->getLine(), App::getExceptionStack());
                if ($this->throwException) {
                    throw $exception;
                } else {
                    App::setExceptionStack($exception);
                }
            }
            if ($mode === 'all') {
                return [];
            } else {
                return false;
            }
        }
    }

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
    public function fetch($sql = null, $reset = false)
    {
        $this->hook($sql);
        $id = sha1($sql);
        if (!isset($this->fetch[$id])) {
            return false;
        }
        if (!$row = $this->fetch[$id]->fetch(\PDO::FETCH_ASSOC)) {
            $this->fetch[$id]->closeCursor();
            unset($this->fetch[$id]);
            return false;
        }
        return $row;
    }

    /**
     * query()の結果を返す
     *
     * @param string $sql
     * @param mixed $response
     * @return mixed
     */
    protected function execMode($sql, $response)
    {
        return $response;
    }

    /**
     * insert,update,deleteされた件数を返す
     *
     * @param string $sql
     * @param mixed $response
     * @return int
     */
    protected function seqMode($sql, $response)
    {
        if (is_bool($response)) {
            return $this->connection->lastInsertId();
        } else {
            $one = $this->query('select last_insert_id()', 'one');
            $response->closeCursor();

            return intval($one);
        }
    }

    /**
     * すべての行を連想配列で返す
     *
     * @param string $sql
     * @param mixed $response
     * @return array
     */
    protected function allMode($sql, $response)
    {
        $all = array();
        while ($row = $response->fetch(\PDO::FETCH_ASSOC)) {
            if (is_array($row) and 'UTF-8' <> $this->charset()) {
                foreach ($row as $key => $val) {
                    if (!is_null($val)) {
                        $_val = mb_convert_encoding($val, 'UTF-8', $this->charset());
                        if ($val === mb_convert_encoding($_val, $this->charset(), 'UTF-8')) {
                            $row[$key] = $_val;
                        }
                    }
                }
            }
            $all[] = $row;
        }
        $response->closeCursor();

        return $all;
    }

    /**
     * 最初の行を配列で返す
     *
     * @param string $sql
     * @param mixed $response
     * @return array
     */
    protected function listMode($sql, $response)
    {
        $list = array();
        while ($row = $response->fetch(\PDO::FETCH_ASSOC)) {
            $one = array_shift($row);
            if (!is_null($one)) {
                $_one = mb_convert_encoding($one, 'UTF-8', $this->charset());
                if ($one === mb_convert_encoding($_one, $this->charset(), 'UTF-8')) {
                    $one = $_one;
                }
            }
            $list[] = $one;
        }
        $response->closeCursor();

        return $list;
    }

    /**
     * 最初の行の最初のcolumnの値を返す
     *
     * @param string $sql
     * @param mixed $response
     * @return string
     */
    protected function oneMode($sql, $response)
    {
        if (!$row = $response->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }
        $one = array_shift($row);
        $response->closeCursor();

        if ('UTF-8' <> $this->charset()) {
            if (!is_null($one)) {
                $_one = mb_convert_encoding($one, 'UTF-8', $this->charset());
                if ($one === mb_convert_encoding($_one, $this->charset(), 'UTF-8')) {
                    $one = $_one;
                }
            }
        }

        return $one;
    }

    /**
     * 最初の行の連想配列を返す
     *
     * @param string $sql
     * @param mixed $response
     * @return array
     */
    protected function rowMode($sql, $response)
    {
        $row = $response->fetch(\PDO::FETCH_ASSOC);
        $response->closeCursor();

        if (is_array($row) and 'UTF-8' <> $this->charset()) {
            foreach ($row as $key => $val) {
                if (!is_null($val)) {
                    $_val = mb_convert_encoding($val, 'UTF-8', $this->charset());
                    if ($val === mb_convert_encoding($_val, $this->charset(), 'UTF-8')) {
                        $row[$key] = $_val;
                    }
                }
            }
        }

        return $row;
    }

    /**
     * Returns metadata for a column in a result set
     *
     * @param int $column
     *
     * @return array
     */
    public function columnMeta($column)
    {
        return $this->statement->getColumnMeta($column);
    }
}
