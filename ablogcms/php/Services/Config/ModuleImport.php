<?php

namespace Acms\Services\Config;

use DB;
use SQL;
use Common;
use Config;

class ModuleImport extends Import
{
    /**
     * import
     *
     * @return void
     */
    protected function import()
    {
        $tables = [
            'module', 'config', 'field'
        ];
        foreach ($tables as $table) {
            $this->insertData($table);
        }
    }

    /**
     * fix sequence
     *
     * @return void
     */
    protected function fixSequence()
    {
        DB::query(SQL::optimizeSeq('module_id', dsn()), 'seq');
    }

    /**
     * register new ids
     *
     * @return void
     */
    protected function registerNewIDs()
    {
        $this->registerNewID('module');
    }

    /**
     * drop data
     *
     * @return void
     */
    protected function dropData()
    {
        if (!isset($this->yaml['module'])) {
            return;
        }

        $modules = $this->yaml['module'];
        $identifiers = [];
        foreach ($modules as $module) {
            $identifiers[] = $module['module_identifier'];
        }

        $SQL = SQL::newSelect('module');
        $SQL->setSelect('module_id');
        $SQL->addWhereIn('module_identifier', $identifiers);
        $SQL->addWhereOpr('module_blog_id', $this->bid);
        $midAry = DB::query($SQL->get(dsn()), 'list');

        if (empty($midAry)) {
            return;
        }

        // delete module
        $SQL = SQL::newDelete('module');
        $SQL->addWhereOpr('module_blog_id', $this->bid);
        $SQL->addWhereIn('module_id', $midAry);
        DB::query($SQL->get(dsn()), 'exec');

        // delete module config
        $SQL = SQL::newDelete('config');
        $SQL->addWhereOpr('config_blog_id', $this->bid);
        $SQL->addWhereIn('config_module_id', $midAry);
        DB::query($SQL->get(dsn()), 'exec');

        Config::cacheClear();

        // delete module field
        $SQL = SQL::newDelete('field');
        $SQL->addWhereOpr('field_blog_id', $this->bid);
        $SQL->addWhereIn('field_mid', $midAry);
        DB::query($SQL->get(dsn()), 'exec');

        foreach ($midAry as $mid) {
            Common::deleteFieldCache('mid', $mid);
        }
    }
}
