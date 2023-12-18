<?php

namespace Tests\Generic;

use PDO;

abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * PDO のインスタンス生成は、クリーンアップおよびフィクスチャ読み込みのときに一度だけ
     */
    static private $pdo = null;

    /**
     * PHPUnit_Extensions_Database_DB_IDatabaseConnection のインスタンス生成は、テストごとに一度だけ
     */
    private $connection = null;

    /**
     * @return \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    final public function getConnection()
    {
        if ( $this->connection === null ) {
            if ( self::$pdo == null ) {
                self::$pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
            }
            $this->connection = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }
        return $this->connection;
    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_CompositeDataSet
     */
    public function getDataSet()
    {
        $compositeDs = new \PHPUnit_Extensions_Database_DataSet_CompositeDataSet([]);

        // fixture配下の.xmlファイルをすべて読み込んでデータセットしてる
        $dir = dirname(__FILE__) . '/../fixture';
        $fh  = opendir($dir);

        while ($file = readdir($fh)) {
            if ( preg_match('/^\./', $file) ) {
                continue;
            }
            if ( preg_match('/\.xml$/', $file) ) {
                $ds = $this->createMySQLXMLDataSet("$dir/$file");
                $compositeDs->addDataSet($ds);
            }
        }
        return $compositeDs;
    }
}


