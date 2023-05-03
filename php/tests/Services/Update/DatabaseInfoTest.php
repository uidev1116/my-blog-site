<?php

namespace Tests\Services\Update;

use Tests\Generic\DatabaseTestCase;
use Acms\Services\Update\DatabaseInfo;

class DatabaseInfoTest extends DatabaseTestCase
{
    protected $target;

    public function setUp()
    {
        $this->target = new DatabaseInfo(dsn());
        parent::setUp();
    }

    /**
     * @test
     */
    public function testSampleData()
    {
        /**
         * 事前データが作成されて、blogテーブルの件数が1件であること
         */
        $this->assertEquals(1, $this->getConnection()->getRowCount('acms_blog'));
    }

    /**
     * @test
     */
    public function testInstance()
    {
        $this->assertInstanceOf('Acms\Services\Update\DatabaseInfo', $this->target);
    }

    /**
     * テーブル一覧の取得
     *
     * @test
     */
    public function testGetTables()
    {
        $tables = $this->target->getTables();
        $this->assertCount(49, $tables, "テーブル数が一致しません。");
    }

    /**
     * テーブルのカラム一覧の取得
     *
     * @test
     */
    public function testGetColumns()
    {
        $columns = $this->target->getColumns('acms_blog');
        $this->assertCount(14, $columns, "カラム数が一致しません。");

        $columns = $this->target->getColumns('acms_field');
        $this->assertCount(10, $columns, "カラム数が一致しません。");

        $columns = $this->target->getColumns('acms_column');
        $this->assertCount(17, $columns, "カラム数が一致しません。");

        $columns = $this->target->getColumns('acms_config');
        $this->assertCount(6, $columns, "カラム数が一致しません。");
    }

    /**
     * カラムのリネーム
     *
     * @test
     */
    public function testRename()
    {
        $def = array(
            'Field' => 'log_access_404',
            'Type' => 'varchar(16)',
            'Null' => 'NO',
            'Default' => ''
        );
        $this->target->rename('acms_log_access', 'log_access_http_status_code', $def, 'log_access_404');
        $columns = $this->target->getColumns('acms_log_access');

        $this->assertArrayHasKey('log_access_404', $columns);
        $this->assertEquals('log_access_404', $columns['log_access_404']['Field']);
    }

    /**
     * カラムの追加
     *
     * @test
     */
    public function testAdd()
    {
        $def = array(
            'Field' => 'log_access_test',
            'Type' => 'varchar(32)',
            'Null' => 'NO',
            'Default' => ''
        );
        $this->target->add('acms_log_access', 'log_access_test', $def, 'log_access_rule_id');
        $columns = $this->target->getColumns('acms_log_access');

        $this->assertArrayHasKey('log_access_test', $columns);
        $this->assertEquals('log_access_test', $columns['log_access_test']['Field']);
    }

    /**
     * カラム定義の変更を適応する
     *
     * @test
     */
    public function testAlterTable()
    {
        // ADD

        // CHANGE

        // RENAME

        // DROP
    }

    /**
     * テーブルを作成する
     *
     * @test
     */
    public function testCreateTable()
    {

    }
}