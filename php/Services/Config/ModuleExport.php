<?php

namespace Acms\Services\Config;

use SQL;
use DB;

class ModuleExport extends Export
{
    /**
     * @var int
     */
    protected $mid;

    /**
     * export module data
     *
     * @param int $bid
     * @param int $mid
     *
     * @return void
     */
    public function exportModule($bid, $mid)
    {
        $this->bid = $bid;
        $this->mid = $mid;

        if ( empty($this->bid) || empty($this->mid) ) {
            return;
        }

        // config
        $this->buildConfigYaml();

        // module
        $this->buildModuleYaml();

        // field
        $this->buildFieldYaml();
    }

    /**
     * @return void
     */
    protected function buildConfigYaml()
    {
        $SQL = SQL::newSelect('config');
        $SQL->addWhereOpr('config_blog_id', $this->bid);
        $SQL->addWhereOpr('config_module_id', $this->mid);
        $SQL->addWhereOpr('config_rule_id', null);
        $q = $SQL->get(dsn());
        DB::query($q, 'fetch');
        $records = array();

        while ( $r = DB::fetch($q) ) {
            $this->extractMetaIds($r);
            $records[] = $r;
        }
        $this->setYaml($records, 'config');
    }

    /**
     * @return void
     */
    protected function buildModuleYaml()
    {
        $SQL = SQL::newSelect('module');
        $SQL->addWhereOpr('module_blog_id', $this->bid);
        $SQL->addWhereOpr('module_id', $this->mid);
        $q = $SQL->get(dsn());
        DB::query($q, 'fetch');
        $records = array();

        while ( $r = DB::fetch($q) ) {
            $this->extractMetaIds($r);
            $records[] = $r;
        }
        $this->setYaml($records, 'module');
    }

    /**
     * @return void
     */
    protected function buildFieldYaml()
    {
        $SQL = SQL::newSelect('field');
        $SQL->addWhereOpr('field_blog_id', $this->bid);
        $SQL->addWhereOpr('field_mid', $this->mid);
        $field = DB::query($SQL->get(dsn()), 'all');
        $this->setYaml($field, 'field');
    }
}

