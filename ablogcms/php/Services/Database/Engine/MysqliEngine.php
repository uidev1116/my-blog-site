<?php

namespace Acms\Services\Database\Engine;

use App;
use mysqli;

class MysqliEngine extends Base
{
    /**
     * @var mixed
     */
    protected $statement;

    /**
     * connect mysql server
     *
     * @param array $dsn
     *
     * @throws \Exception
     */
    public function connect($dsn)
    {
        try {
            mysqli_report(MYSQLI_REPORT_STRICT);

            $host = $dsn['host'];
            $charset = $this->getCharset($dsn);
            $db_name = '';
            $port = '3306';
            if (!empty($dsn['port'])) {
                $port = $dsn['port'];
            }
            if (!empty($dsn['name'])) {
                $db_name = $dsn['name'];
            }
            $this->connection = new mysqli($host, $dsn['user'], $dsn['pass'], $db_name, $port);
            $this->connection->set_charset($charset);
        } catch (\Exception $e) {
            throw $e;
        }

        if ($this->connection->connect_error) {
            throw new \RuntimeException('Connect Error (' . $this->connection->connect_errno . ') '
                . $this->connection->connect_error);
        }

        $this->debug = !empty($dsn['debug']);
        $this->dsn = array(
            'type' => isset($dsn['type']) ? $dsn['type'] : null,
            'debug' => $this->debug,
            'charset' => $charset,
        );
    }

    /**
     * @param $dsn
     * @return void
     */
    public function reconnect($dsn)
    {
        // TODO: Implement reconnect() method.
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
        return "'" . $DB->connection->escape_string($string) . "'";
    }


    /**
     * Get SQL Server Version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->connection->server_info;
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
     * @return array|bool|resource|int
     *
     * @throws \Exception
     */
    public function query($sql, $mode = 'row')
    {
        try {
            $start_time = microtime(true);
            $res = $this->connection->query($sql);
            $exe_time = sprintf('%0.6f', microtime(true) - $start_time);
            $this->saveProcessingTime($sql, $exe_time);
            $this->affectedRows = $res ? $this->connection->affected_rows : 0;
            $this->columnCount = $res ? $this->connection->field_count : 0;
            $this->statement = $res;

            if ($res === false) {
                throw new \RuntimeException($this->connection->errno . ": " . $this->connection->error . " sql: " . $sql);
            }

            $method = strtolower($mode) . 'Mode';
            if (method_exists($this, $method)) {
                return $this->{$method}($sql, $res); // @phpstan-ignore-line
            } else {
                return $this->etcMode($sql, $res);
            }
        } catch (\Exception $e) {
            if ($this->debug) {
                throw $e;
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
        $id = !empty($sql) ? sha1($sql) : '';
        if (empty($this->fetch[$id])) {
            if (empty($id)) {
                if (empty($this->fetch)) {
                    return false;
                }
                $this->fetch[$id] = array_shift($this->fetch);
            } else {
                return false;
            }
        }

        if (!$row = $this->fetch[$id]->fetch_assoc()) {
            $this->fetch[$id]->free_result();
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
        return $this->affected_rows();
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
        while ($row = $response->fetch_assoc()) {
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
        $response->free_result();

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
        while ($row = $response->fetch_assoc()) {
            $one = array_shift($row);
            if (!is_null($one)) {
                $_one = mb_convert_encoding($one, 'UTF-8', $this->charset());
                if ($one === mb_convert_encoding($_one, $this->charset(), 'UTF-8')) {
                    $one = $_one;
                }
            }
            $list[] = $one;
        }
        $response->free_result();

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
        if (!$row = $response->fetch_assoc()) {
            return false;
        }
        $one = array_shift($row);

        if ('UTF-8' <> $this->charset()) {
            if (!is_null($one)) {
                $_one = mb_convert_encoding($one, 'UTF-8', $this->charset());
                if ($one === mb_convert_encoding($_one, $this->charset(), 'UTF-8')) {
                    $one = $_one;
                }
            }
        }
        $response->free_result();

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
        $row = $response->fetch_assoc();
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
        $response->free_result();

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
        } catch (\Exception $e) {
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
}
