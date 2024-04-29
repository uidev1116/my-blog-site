<?php

class ACMS_GET_Entry_TagRelational extends ACMS_GET_Entry_Summary
{
    public $_axis = [
        'bid' => 'self',
        'cid' => 'self',
    ];

    public $_scope = [
        'eid' => 'global',
    ];

    /**
     * コンフィグの取得
     *
     * @return array
     */
    function initVars()
    {
        return [
            'order' => $this->order ? $this->order : config('entry_tag-relational_order'),
            'limit' => intval(config('entry_tag-relational_limit')),
            'indexing' => config('entry_tag-relational_indexing'),
            'membersOnly' => config('entry_tag-relational_members_only'),
            'secret' => config('entry_tag-relational_secret'),
            'notfound' => config('mo_entry_tag-relational_notfound'),
            'notfoundStatus404' => config('entry_tag-relational_notfound_status_404'),
            'noimage' => config('entry_tag-relational_noimage'),
            'imageX' => intval(config('entry_tag-relational_image_x')),
            'imageY' => intval(config('entry_tag-relational_image_y')),
            'imageTrim' => config('entry_tag-relational_image_trim'),
            'imageZoom' => config('entry_tag-relational_image_zoom'),
            'imageCenter' => config('entry_tag-relational_image_center'),
            'offset' => config('entry_tag-relational_offset'),
            'unit' => config('entry_tag-relational_unit'),
            'newtime' => config('entry_tag-relational_newtime'),
            'loop_class' => config('entry_tag-relational_loop_class'),
            'fulltextWidth' => config('entry_tag-relational_fulltext_width'),
            'fulltextMarker' => config('entry_tag-relational_fulltext_marker'),
            'entryFieldOn' => config('entry_tag-relational_entry_field'),
            'categoryInfoOn' => config('entry_tag-relational_category_on'),
            'categoryFieldOn' => config('entry_tag-relational_category_field_on'),
            'userInfoOn' => config('entry_tag-relational_user_on'),
            'userFieldOn' => config('entry_tag-relational_user_field_on'),
            'blogInfoOn' => config('entry_tag-relational_blog_on'),
            'blogFieldOn' => config('entry_tag-relational_blog_field_on'),
        ];
    }

    /**
     * 起動
     *
     * @return string
     */
    function get()
    {
        if (!$this->setConfig()) {
            return '';
        }
        $DB = DB::singleton(dsn());
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        if (empty($this->eid)) {
            return '';
        }
        $q = $this->buildQuery();
        $this->entries = $DB->query($q, 'all');
        if (count($this->entries) > $this->config['limit']) {
            array_pop($this->entries);
        }

        $this->buildEntries($Tpl);

        if ($this->buildNotFound($Tpl)) {
            return $Tpl->get();
        }
        if (empty($this->entries)) {
            return '';
        }
        $vars = $this->getRootVars();
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }

    /**
     * sqlの組み立て
     *
     * @return SQL_Select
     */
    function buildQuery()
    {
        $SQL = SQL::newSelect('entry');
        $SQL->addSelect('* ');
        $SQL->addSelect('tag_name', 'tag_similar_grade', null, 'count');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        $SQL->addLeftJoin('tag', 'tag_entry_id', 'entry_id');

        $this->filterQuery($SQL);

        $Tag = SQL::newSelect('tag');
        $Tag->addSelect('tag_name');
        $Tag->addWhereOpr('tag_entry_id', $this->eid);

        $SQL->addWhereIn('tag_name', $Tag);
        $SQL->addWhereOpr('entry_id', $this->eid, '!=');

        $this->categoryFilterQuery($SQL);
        $this->filterSubQuery($SQL);

        $this->setAmount($SQL);
        $this->orderQuery($SQL);
        $this->limitQuery($SQL);
        $SQL->addGroup('entry_id');

        return $SQL->get(dsn());
    }

    /**
     * 絞り込みクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function filterQuery(&$SQL)
    {
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);

        if ('on' === $this->config['secret']) {
            ACMS_Filter::blogDisclosureSecretStatus($SQL);
        } else {
            ACMS_Filter::blogStatus($SQL);
        }
        if (!empty($this->keyword)) {
            ACMS_Filter::entryKeyword($SQL, $this->keyword);
        }
        if (!empty($this->Field)) {
            ACMS_Filter::entryField($SQL, $this->Field);
        }
        if ('on' === $this->config['indexing']) {
            $SQL->addWhereOpr('entry_indexing', 'on');
        }
        if (isset($this->config['membersOnly']) && 'on' === $this->config['membersOnly']) {
            $SQL->addWhereOpr('entry_members_only', 'on');
        }
        if ('on' <> $this->config['noimage']) {
            $SQL->addWhereOpr('entry_primary_image', null, '<>');
        }
    }

    /**
     * orderクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function orderQuery(&$SQL)
    {
        ACMS_Filter::entryOrder($SQL, $this->config['order'], $this->uid, $this->cid);
        if ($this->config['order'] === 'relationality') {
            $SQL->setOrder('tag_similar_grade', 'DESC');
            $SQL->addOrder('entry_datetime', 'DESC');
        }
    }

    /**
     * テンプレートの組み立て
     *
     * @param Template &$Tpl
     * @return void
     */
    public function buildEntries(&$Tpl)
    {
        $gluePoint = count($this->entries);
        foreach ($this->entries as $i => $row) {
            $i++;
            $this->buildSummary(
                $Tpl,
                $row,
                $i,
                $gluePoint,
                $this->config,
                ['grade' => 'tag_similar_grade'],
                $this->eagerLoad()
            );
        }
    }

    /**
     * タグのEagerLoading
     *
     * @return array|bool
     */
    protected function tagEagerLoad()
    {
        return false;
    }
}
