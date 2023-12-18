<?php

namespace Acms\Services\Blog;

use SQL;
use DB;
use Common;
use Symfony\Component\Yaml\Yaml;

class Import
{
    /**
     * @var array
     */
    protected $yaml;

    /**
     * @var int
     */
    protected $bid;

    /**
     * @var int
     */
    protected $uid;

    /**
     * @var array
     */
    protected $ids;

    /**
     * @var array
     */
    protected $errors;

    /**
     * @var array
     */
    protected $mediaFieldFix = array();

    /**
     * Import constructor
     */
    public function __construct()
    {
        $this->uid = SUID;
    }

    /**
     * import blog data
     *
     * @param int $bid
     * @param string $yaml
     *
     * @return array
     */
    public function run($bid, $yaml)
    {
        $this->bid = $bid;
        $this->yaml = Yaml::parse($yaml);
        $this->ids = array();
        $this->errors = array();

        $this->dropData();
        $this->registerNewIDs();

        $tables = array(
            'category', 'entry', 'tag',
            'module', 'layout_grid',
            'rule', 'config', 'column', 'config_set',
            'dashboard', 'field', 'media', 'media_tag',
        );
        foreach ( $tables as $table ) {
            $this->insertData($table);
        }
        $this->updateBlogConfigSet();

        Common::flushCache();
        $this->generateFulltext();

        return $this->errors;
    }

    private function generateFulltext()
    {
        $DB = DB::singleton(dsn());
        foreach (array('category', 'entry') as $type) {
            $SQL = SQL::newSelect($type);
            $SQL->addSelect($type.'_id');
            $SQL->addWhereOpr($type.'_blog_id', $this->bid);
            $all = $DB->query($SQL->get(dsn()), 'all');

            foreach ( $all as $row ) {
                $id = $row[$type.'_id'];
                switch ( $type ) {
                    case 'category':
                        Common::saveFulltext('cid', $id, Common::loadCategoryFulltext($id));
                        break;
                    case 'entry':
                        Common::saveFulltext('eid', $id, Common::loadEntryFulltext($id));
                        break;
                }
            }
        }
    }

    /**
     * @param string $table
     * @return void
     */
    private function insertData($table)
    {
        if (!$this->existsYaml($table)) {
            return;
        }
        foreach ($this->yaml[$table] as $record) {
            $SQL = SQL::newInsert($table);
            foreach ( $record as $field => $value ) {
                $value = $this->fix($table, $field, $value);
                if (is_callable(array($this, $table . 'Fix'))) {
                    $value = call_user_func_array(array($this, $table . 'Fix'), array($field, $value, $record));
                }
                if ($value !== false) {
                    $SQL->addInsert($field, $value);
                }
            }
            try {
                DB::query($SQL->get(dsn()), 'exec');
            } catch ( \Exception $e ) {
                $this->errors[] = $e->getMessage();
            }
        }
        foreach ($this->mediaFieldFix as $data) {
            $SQL = SQL::newUpdate('field');
            $SQL->addUpdate('field_value', $data['value']);
            $SQL->addWhereOpr('field_sort', $data['sort']);
            $SQL->addWhereOpr('field_key', $data['name']);
            $SQL->addWhereOpr('field_eid', $data['eid']);
            $SQL->addWhereOpr('field_cid', $data['cid']);
            $SQL->addWhereOpr('field_uid', $data['uid']);
            $SQL->addWhereOpr('field_bid', $data['bid']);
            $SQL->addWhereOpr('field_mid', $data['mid']);
            DB::query($SQL->get(dsn()), 'exec');
        }
    }

    /**
     * @return void
     */
    private function updateBlogConfigSet()
    {
        if (!$this->existsYaml('blog')) {
            return;
        }
        if (!isset($this->yaml['blog'][0])) {
            return;
        }
        $blog = $this->yaml['blog'][0];

        $sql = SQL::newUpdate('blog');
        if (isset($blog['blog_config_set_id'])) {
            $sql->addUpdate('blog_config_set_id', $this->getNewID('config_set', $blog['blog_config_set_id']));
        }
        if (isset($blog['blog_config_set_scope'])) {
            $sql->addUpdate('blog_config_set_scope', $blog['blog_config_set_scope']);
        }
        if (isset($blog['blog_theme_set_id'])) {
            $sql->addUpdate('blog_theme_set_id', $this->getNewID('config_set', $blog['blog_theme_set_id']));
        }
        if (isset($blog['blog_theme_set_scope'])) {
            $sql->addUpdate('blog_theme_set_scope', $blog['blog_theme_set_scope']);
        }
        if (isset($blog['blog_editor_set_id'])) {
            $sql->addUpdate('blog_editor_set_id', $this->getNewID('config_set', $blog['blog_editor_set_id']));
        }
        if (isset($blog['blog_editor_set_scope'])) {
            $sql->addUpdate('blog_editor_set_scope', $blog['blog_editor_set_scope']);
        }
        $sql->addWhereOpr('blog_id', $this->bid);
        DB::query($sql->get(dsn()), 'exec');
    }

    /**
     * @param string $table
     * @param string $field
     * @param string|null $value
     *
     * @return int|null
     */
    private function fix($table, $field, $value)
    {
        if (is_null($value)) {
            return null;
        }

        $key = substr($field, strlen($table . '_'));
        if ( $key === 'id' ) {
            $value = $this->getNewID($table, $value);
        } else if ( $key === 'category_id' ) {
            $value = $this->getNewID('category', $value);
        } else if ( $key === 'user_id' ) {
            $value = $this->getNewID('user', $value);
        } else if ( $key === 'entry_id' ) {
            $value = $this->getNewID('entry', $value);
        } else if ( $key === 'rule_id' ) {
            $value = $this->getNewID('rule', $value);
        } else if ( $key === 'module_id' ) {
            $value = $this->getNewID('module', $value);
        } else if ( $key === 'media_id') {
            $value = $this->getNewID('media', $value);
        } else if ( $key === 'set_id' || $key === 'config_set_id' || $key === 'theme_set_id' || $key === 'editor_set_id' ) {
            $value = $this->getNewID('config_set', $value);
        } else if ( $key === 'blog_id' ) {
            $value = $this->bid;
        }

        return $value;
    }

    /**
     * @param string $field
     * @param string|null $value
     * @param array $record
     *
     * @return mixed
     */
    private function configFix($field, $value, $record)
    {
        if (!is_null($value) && $record['config_key'] === 'media_banner_mid' && $field === 'config_value') {
            return $this->getNewID('media', $value);
        }
        return $value;
    }

    /**
     * @param string $field
     * @param string|null $value
     * @param array $record
     *
     * @return mixed
     */
    private function entryFix($field, $value, $record)
    {
        if ( $field === 'entry_current_rev_id' ) {
            $value = 0;
        } else if ( $field === 'entry_last_update_user_id' ) {
            $value = $this->uid;
        } else if ( $field === 'entry_primary_image' && !empty($value) ) {
            $value = $this->getNewID('column', $value);
        }
        return $value;
    }

    /**
     * @param string $field
     * @param string|null $value
     * @param array $record
     *
     * @return mixed
     */
    private function columnFix($field, $value, $record)
    {
        if (strncmp($record['column_type'], 'custom', 6) === 0 && $field === 'column_field_6') {
            $data = acmsUnserialize($value);
            if (method_exists($data, 'deleteField')) {
                $fixMediaField = array();
                foreach ($data->listFields() as $fd) {
                    foreach ($data->getArray($fd, true) as $i => $val) {
                        if (strpos($fd, '@media') !== false) {
                            $sourceFd = substr($fd, 0, -6);
                            if (!isset($fixMediaField[$sourceFd])) {
                                $fixMediaField[$sourceFd] = array();
                            }
                            $val = $this->getNewID('media', $val);
                            $fixMediaField[$sourceFd][] = $val;
                        } else {
                            $val = preg_replace('@([\d]{3})/(.*)\.([^\.]{2,6})@ui', sprintf("%03d", BID) . '/$2.$3', $val, -1);
                        }
                        if ($i === 0) {
                            $data->set($fd, $val);
                        } else {
                            $data->add($fd, $val);
                        }
                    }
                }
                // fix media id
                foreach ($fixMediaField as $fd => $mediaIds) {
                    foreach ($mediaIds as $j => $mid) {
                        if ($j === 0) {
                            $data->set($fd, $mid);
                        } else {
                            $data->add($fd, $mid);
                        }
                    }
                }
                return acmsSerialize($data);
            }
        } else if (1 &&
            !is_null($value) &&
            strncmp($record['column_type'], 'media', 5) === 0 &&
            $field === 'column_field_1'
        ) {
            $value = $this->getNewID('media', $value);
        } else if (1 &&
            !is_null($value) &
            strncmp($record['column_type'], 'module', 5) === 0 &&
            $field === 'column_field_1'
        ) {
            $value = $this->getNewID('module', $value);
        }
        return $value;
    }

    /**
     * @param string $field
     * @param string|null $value
     * @param array $record
     *
     * @return mixed
     */
    private function categoryFix($field, $value, $record)
    {
        if (!is_null($value) && $field === 'category_parent') {
            $value = $this->getNewID('category', $value);
        }
        return $value;
    }

    /**
     * @param string $field
     * @param string|null $value
     * @param array $record
     *
     * @return mixed
     */
    private function fulltextFix($field, $value, $record)
    {
        if (!is_null($value) && $field === 'fulltext_eid') {
            $value = $this->getNewID('entry', $value);
        } elseif (!is_null($value) && $field === 'fulltext_cid') {
            $value = $this->getNewID('category', $value);
        } elseif (!is_null($value) && $field === 'fulltext_uid') {
            $value = $this->getNewID('user', $value);
        } elseif (!empty($value) && $field === 'fulltext_bid') {
            $value = false;
        } elseif (empty($value) && $field === 'fulltext_ngram') {
            $value = '';
        }

        return $value;
    }

    /**
     * @param string $field
     * @param string|null $value
     * @param array $record
     *
     * @return mixed
     */
    private function moduleFix($field, $value, $record)
    {
        if (!is_null($value) && $field === 'module_eid' ) {
            $value = $this->getNewID('entry', $value);
        } else if (!is_null($value) && $field === 'module_cid' ) {
            $value = $this->getNewID('category', $value);
        } else if (!is_null($value) && $field === 'module_uid' ) {
            $value = $this->getNewID('user', $value);
        } else if ( $field === 'module_bid' ) {
            $value = empty($value) ? null : $value;
        }
        return $value;
    }

    /**
     * @param string $field
     * @param string|null $value
     * @param array $record
     *
     * @return mixed
     */
    private function layout_gridFix($field, $value, $record)
    {
        if (!is_null($value) && $field === 'layout_grid_mid') {
            $value = $this->getNewID('module', $value);
        }
        return $value;
    }


    /**
     * @param string $field
     * @param string|null $value
     * @param array $record
     *
     * @return mixed
     */
    private function ruleFix($field, $value, $record)
    {
        if (!is_null($value) && $field === 'rule_eid') {
            $value = $this->getNewID('entry', $value);
        } else if (!is_null($value) && $field === 'rule_cid') {
            $value = $this->getNewID('category', $value);
        } else if (!is_null($value) && $field === 'rule_uid') {
            $value = $this->getNewID('user', $value);
        } else if (!is_null($value) && $field === 'rule_aid' ) {
            $value = $this->getNewID('alias', $value);
        }
        return $value;
    }

    /**
     * @param string $field
     * @param string|null $value
     * @param array $record
     *
     * @return mixed
     */
    private function fieldFix($field, $value, $record)
    {
        if (!is_null($value) && $field === 'field_eid') {
            $value = $this->getNewID('entry', $value);
        } else if (!is_null($value) && $field === 'field_cid') {
            $value = $this->getNewID('category', $value);
        } else if (!is_null($value) && $field === 'field_uid') {
            $value = $this->getNewID('user', $value);
        } else if (!is_null($value) && $field === 'field_mid') {
            $value = $this->getNewID('module', $value);
        } else if ( $field === 'field_bid' && !empty($value)) {
            $value = $this->bid;
        } else if (1 &&
            !is_null($value) &&
            $field === 'field_value' &&
            preg_match('/@media$/', $record['field_key'])
        ) {
            $value = $this->getNewID('media', $value);
            $this->mediaFieldFix[] = array(
                'name' => substr($record['field_key'], 0, -6),
                'value' => $value,
                'sort' => $record['field_sort'],
                'eid' => empty($record['field_eid']) ? null : $this->getNewID('entry', $record['field_eid']),
                'cid' => empty($record['field_cid']) ? null : $this->getNewID('category', $record['field_cid']),
                'uid' => empty($record['field_uid']) ? null : $this->getNewID('user', $record['field_uid']),
                'bid' => empty($record['field_bid']) ? null : $this->bid,
                'mid' => empty($record['field_mid']) ? null : $this->getNewID('module', $record['field_mid']),
            );
        }
        return $value;
    }

    /**
     * @param string $table
     * @param int|string $id
     *
     * @return int|string
     */
    private function getNewID($table, $id)
    {
        if (is_numeric($id)) {
            if (!isset($this->ids[$table][$id])) {
                return $id;
            }
            return $this->ids[$table][$id];
        }
        if (strpos($id, ':acms_unit_delimiter:') !== false) {
            $temp = explode(':acms_unit_delimiter:', $id);
            $responseIds = [];
            foreach ($temp as $tempId) {
                if (!isset($this->ids[$table][$tempId])) {
                    $responseIds[] = $tempId;
                    continue;
                }
                $responseIds[] = $this->ids[$table][$tempId];
            }
            return implode(':acms_unit_delimiter:', $responseIds);
        }
    }

    /**
     * @return void
     */
    private function registerNewIDs()
    {
        $tables = array(
            'category', 'column', 'alias',
            'entry', 'fulltext', 'media',
            'module', 'rule', 'media', 'config_set',
        );

        foreach ( $tables as $table ) {
            $this->registerNewID($table);
        }
    }

    /**
     * @param string $table
     *
     * @return void
     */
    private function registerNewID($table)
    {
        if ( !$this->existsYaml($table) ) {
            return;
        }
        foreach ( $this->yaml[$table] as $record ) {
            if ( !isset($record[$table . '_id']) ) {
                continue;
            }
            $id = $record[$table . '_id'];
            if ( isset($this->ids[$table][$id]) ) {
                continue;
            }
            $this->ids[$table][$id] = DB::query(SQL::nextval($table . '_id', dsn()), 'seq');
        }
    }

    /**
     * check yaml data
     *
     * @param $table
     *
     * @return bool
     */
    private function existsYaml($table)
    {
        if ( !isset($this->yaml[$table]) ) {
            return false;
        }
        $data = $this->yaml[$table];
        if ( !is_array($data) ) {
            return false;
        }
        return true;
    }

    /**
     * drop blog data
     *
     * @return void
     */
    private function dropData()
    {
        $tables = array(
            'category', 'entry', 'column', 'tag',
            'fulltext', 'field', 'media', 'media_tag',
            'approval', 'cache_reserve', 'column_rev', 'entry_rev',
            'field_rev', 'tag_rev',
            'dashboard', 'module', 'layout_grid', 'rule', 'config', 'config_set',
        );

        foreach ( $tables as $table ) {
            $this->clearTable($table);
        }
    }

    /**
     * clear table in database
     *
     * @param string $table
     *
     * @return void
     */
    private function clearTable($table)
    {
        $SQL = SQL::newDelete($table);
        if ( preg_match('/^(.*)\_rev$/', $table, $match) ) {
            $SQL->addWhereOpr($match[1].'_blog_id', $this->bid);
        } else {
            $SQL->addWhereOpr($table.'_blog_id', $this->bid);
        }
        if ($table === 'field') {
            $SQL->addWhereOpr('field_uid', null);
        }
        DB::query($SQL->get(dsn()), 'exec');
    }
}
