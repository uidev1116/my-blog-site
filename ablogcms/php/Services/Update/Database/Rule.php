<?php

namespace Acms\Services\Update\Database;

use DB;
use SQL;

class Rule
{
    /**
     * @var string
     */
    protected $fromVersion;

    /**
     * @var string
     */
    protected $toVersion;

    /**
     * 例外的なアップデートを実行
     *
     * @param string $fromVersion
     * @param string $toVersion
     */
    public function update($fromVersion, $toVersion)
    {
        $this->fromVersion = $fromVersion;
        $this->toVersion = $toVersion;

        // v1.4.0以前
        if (version_compare($this->fromVersion, '1.4.0', '<')) {
            $this->update140();
        }

        // v1.4.2以前
        if (version_compare($this->fromVersion, '1.4.2', '<')) {
            $this->update142();
        }

        // v1.5.0以前
        if (version_compare($this->fromVersion, '1.5.0', '<')) {
            $this->update150();
        }

        // v2.10.0以前
        if (version_compare($this->fromVersion, '2.10.0', '<')) {
            $this->update2100();
        }
    }

    /**
     * フィールドグループのコンフィグを追加
     *
     * @param string $group
     * @param array $vals
     * @param null|int $bid
     * @param null|int $rid
     * @param null|int $mid
     */
    protected function addGroupConfig($group, $vals, $bid, $rid = null, $mid = null)
    {
        $DB = DB::singleton(dsn());
        foreach ($vals as $val) {
            $SQL = SQL::newInsert('config');
            $SQL->addInsert('config_key', $group);
            $SQL->addInsert('config_value', $val);
            $SQL->addInsert('config_sort', '0');
            if (!empty($mid)) {
                $SQL->addInsert('config_module_id', $mid);
            } elseif (!empty($rid)) {
                $SQL->addInsert('config_rule_id', $rid);
            }
            $SQL->addInsert('config_blog_id', $bid);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    /**
     * navigation_publish = on を追加
     *
     * @param int $bid
     * @param null|int $rid
     * @param null|int $mid
     */
    protected function addNavigationPublish($bid, $rid = null, $mid = null)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('config');
        $SQL->addWhereOpr('config_key', 'navigation_target');
        $SQL->addWhereOpr('config_rule_id', $rid);
        $SQL->addWhereOpr('config_module_id', $mid);
        $SQL->addWhereOpr('config_blog_id', $bid);
        $SQL->addSelect('*', 'row_amount', null, 'COUNT');
        $SQL->addSelect('config_sort', 'max_sort', null, 'MAX');
        $res = $DB->query($SQL->get(dsn()), 'row');

        if (empty($res)) {
            return;
        }

        $range = range($res['max_sort'] + 1, $res['max_sort'] + $res['row_amount']);

        foreach ($range as $sort) {
            $SQL = SQL::newInsert('config');
            $SQL->addInsert('config_key', 'navigation_publish');
            $SQL->addInsert('config_value', 'on');
            $SQL->addInsert('config_sort', $sort);
            $SQL->addInsert('config_rule_id', $rid);
            $SQL->addInsert('config_module_id', $mid);
            $SQL->addInsert('config_blog_id', $bid);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    /**
     * v1.4.0以前からのアップデート
     * コンフィグ画面用のconfigフィールドグループを追加
     */
    private function update140()
    {
        $DB = DB::singleton(dsn());

        // モジュールIDを探索
        $SQL = SQL::newSelect('module');
        $SQL->addWhereOpr('module_name', 'Links');
        $mods = $DB->query($SQL->get(dsn()), 'all');

        foreach ($mods as $mod) {
            $mid = $mod['module_id'];
            $bid = $mod['module_blog_id'];

            $this->addGroupConfig('@linkgroup', ['links_value', 'links_label'], $bid, null, $mid);
        }

        // ルールを探索
        $SQL = SQL::newSelect('rule');
        $rules = $DB->query($SQL->get(dsn()), 'all');

        foreach ($rules as $rule) {
            $rid = $rule['rule_id'];
            $bid = $rule['rule_blog_id'];

            $this->addGroupConfig(
                '@linkgroup',
                ['links_value', 'links_label'],
                $bid,
                $rid,
                null
            );
            $this->addGroupConfig(
                '@addtype_group',
                ['addtype_mimetype', 'addtype_extension'],
                $bid,
                $rid,
                null
            );
            $this->addGroupConfig(
                '@column_text_tag_group',
                ['column_text_tag', 'column_text_tag_label'],
                $bid,
                $rid,
                null
            );
            $this->addGroupConfig(
                '@column_image_size_group',
                ['column_image_size', 'column_image_size_label'],
                $bid,
                $rid,
                null
            );
            $this->addGroupConfig(
                '@column_map_size_group',
                ['column_map_size', 'column_map_size_label'],
                $bid,
                $rid,
                null
            );
            $this->addGroupConfig(
                '@column_youtube_size_group',
                ['column_youtube_size', 'column_youtube_size_label'],
                $bid,
                $rid,
                null
            );
            $this->addGroupConfig(
                '@column_eximage_size_group',
                ['column_eximage_size', 'column_eximage_size_label'],
                $bid,
                $rid,
                null
            );
            $this->addGroupConfig(
                '@column_add_type_group',
                ['column_add_type', 'column_add_type_label'],
                $bid,
                $rid,
                null
            );
        }
    }

    /**
     * v1.4.2以前からのアップデート
     * Api_Yahoo_* の名前変更対応
     */
    private function update142()
    {
        $DB = DB::singleton(dsn());

        $SQL = SQL::newUpdate('module');
        $SQL->addUpdate('module_name', 'Api_Yahoo_WebSearch');
        $SQL->addWhereOpr('module_name', 'Api_YahooWebSearch');
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL = SQL::newUpdate('module');
        $SQL->addUpdate('module_name', 'Api_Yahoo_ImageSearch');
        $SQL->addWhereOpr('module_name', 'Api_YahooImageSearch');
        $DB->query($SQL->get(dsn()), 'exec');

        // ユーザーfulltextを生成・追加
        $SQL = SQL::newSelect('user');
        $q = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        while ($row = $DB->fetch($q)) {
            // user
            $user = array(
                $row['user_name'],
                $row['user_code'],
                $row['user_mail'],
                $row['user_mail_mobile'],
                $row['user_url']
            );
            $uid = $row['user_id'];
            $bid = $row['user_blog_id'];

            // meta
            $meta = array();
            $SQL = SQL::newSelect('field');
            $SQL->addSelect('field_value');
            $SQL->addWhereOpr('field_search', 'on');
            $SQL->addWhereOpr('field_uid', $uid);
            $_q = $SQL->get(dsn());

            if ($DB->query($_q, 'fetch') and ($_row = $DB->fetch($_q))) {
                do {
                    $meta[] = $_row['field_value'];
                } while ($_row = $DB->fetch($_q));
            }

            // merge
            $user = preg_replace('@\s+@', ' ', strip_tags(implode(' ', $user)));
            $meta = preg_replace('@\s+@', ' ', strip_tags(implode(' ', $meta)));
            $fulltext = $user . "\x0d\x0a\x0a\x0d" . $meta;

            // delete
            $SQL = SQL::newDelete('fulltext');
            $SQL->addWhereOpr('fulltext_uid', $uid);
            $DB->query($SQL->get(dsn()), 'exec');

            // save
            $SQL = SQL::newInsert('fulltext');
            $SQL->addInsert('fulltext_value', $fulltext);
            $SQL->addInsert('fulltext_uid', $uid);
            $SQL->addInsert('fulltext_blog_id', $bid);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }

    /**
     * v1.5.0以前からのアップデート
     * navigation_publish = on を追加
     */
    private function update150()
    {
        $DB = DB::singleton(dsn());

        // モジュールIDを探索
        $SQL = SQL::newSelect('module');
        $SQL->addWhereOpr('module_name', 'Navigation');
        $mods = $DB->query($SQL->get(dsn()), 'all');

        if (!empty($mods)) {
            foreach ($mods as $mod) {
                $mid = $mod['module_id'];
                $bid = $mod['module_blog_id'];

                $this->addNavigationPublish($bid, null, $mid);
            }
        }

        // ルールを探索
        $SQL = SQL::newSelect('rule');
        $rules = $DB->query($SQL->get(dsn()), 'all');

        if (!empty($mods)) {
            foreach ($rules as $rule) {
                $rid = $rule['rule_id'];
                $bid = $rule['rule_blog_id'];

                $this->addNavigationPublish($bid, $rid, null);
            }
        }

        // デフォルトを探索
        $SQL = SQL::newSelect('blog');
        $blogs = $DB->query($SQL->get(dsn()), 'all');

        if (!empty($blogs)) {
            foreach ($blogs as $blog) {
                $bid = $blog['blog_id'];

                $this->addNavigationPublish($bid, null, null);
            }
        }
    }

    /**
     * v2.10.0以前からのアップデート
     * workflowを
     */
    private function update2100()
    {
        $DB = DB::singleton(dsn());

        $startList = array();
        $SQL = SQL::newSelect('config');
        $SQL->addWhereOpr('config_key', 'workflow_start_group');
        $q = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        while ($row = $DB->fetch($q)) {
            $startList[$row['config_blog_id']][] = $row['config_value'];
        }

        $lastList = array();
        $SQL = SQL::newSelect('config');
        $SQL->addWhereOpr('config_key', 'workflow_last_group');
        $q = $SQL->get(dsn());
        $DB->query($q, 'fetch');
        while ($row = $DB->fetch($q)) {
            $lastList[$row['config_blog_id']][] = $row['config_value'];
        }

        foreach ($startList as $bid => $items) {
            $SQL = SQL::newUpdate('workflow');
            $SQL->addUpdate('workflow_start_group', implode(',', $items));
            $SQL->addWhereOpr('workflow_blog_id', $bid);
            $SQL->addWhereOpr('workflow_category_id', null);
            $DB->query($SQL->get(dsn()), 'exec');
        }

        foreach ($lastList as $bid => $items) {
            $SQL = SQL::newUpdate('workflow');
            $SQL->addUpdate('workflow_last_group', implode(',', $items));
            $SQL->addWhereOpr('workflow_blog_id', $bid);
            $SQL->addWhereOpr('workflow_category_id', null);
            $DB->query($SQL->get(dsn()), 'exec');
        }
    }
}
