<?php

class ACMS_GET_Entry_Summary extends ACMS_GET_Entry
{
    protected $config;
    protected $entries;
    protected $amount;
    protected $eids;

    protected $blogSubQuery;
    protected $categorySubQuery;
    protected $filterCategoryFieldName = 'entry_category_id';
    protected $sortFields = [];

    /**
     * @inheritDoc
     */
    public $_axis = [ // phpcs:ignore
        'bid'   => 'self',
        'cid'   => 'self',
    ];

    /**
     * コンフィグの取得
     *
     * @return array
     */
    function initVars()
    {
        return [
            'order' => [
                $this->order ? $this->order : config('entry_summary_order'),
                config('entry_summary_order2'),
            ],
            'orderFieldName'        => config('entry_summary_order_field_name'),
            'noNarrowDownSort'      => config('entry_summary_no_narrow_down_sort', 'off'),
            'limit'                 => intval(config('entry_summary_limit')),
            'offset'                => intval(config('entry_summary_offset')),
            'indexing'              => config('entry_summary_indexing'),
            'membersOnly'           => config('entry_summary_members_only'),
            'subCategory'           => config('entry_summary_sub_category'),
            'secret'                => config('entry_summary_secret'),
            'notfound'              => config('mo_entry_summary_notfound'),
            'notfoundStatus404'     => config('entry_summary_notfound_status_404'),
            'noimage'               => config('entry_summary_noimage'),
            'pagerDelta'            => config('entry_summary_pager_delta'),
            'pagerCurAttr'          => config('entry_summary_pager_cur_attr'),

            'unit'                  => config('entry_summary_unit'),
            'newtime'               => config('entry_summary_newtime'),
            'imageX'                => intval(config('entry_summary_image_x')),
            'imageY'                => intval(config('entry_summary_image_y')),
            'imageTrim'             => config('entry_summary_image_trim'),
            'imageZoom'             => config('entry_summary_image_zoom'),
            'imageCenter'           => config('entry_summary_image_center'),

            'entryFieldOn'          => config('entry_summary_entry_field'),
            'relatedEntryOn'        => config('entry_summary_related_entry_on', 'off'),
            'categoryInfoOn'        => config('entry_summary_category_on'),
            'categoryFieldOn'       => config('entry_summary_category_field_on'),
            'userInfoOn'            => config('entry_summary_user_on'),
            'userFieldOn'           => config('entry_summary_user_field_on'),
            'blogInfoOn'            => config('entry_summary_blog_on'),
            'blogFieldOn'           => config('entry_summary_blog_field_on'),
            'pagerOn'               => config('entry_summary_pager_on'),
            'simplePagerOn'         => config('entry_summary_simple_pager_on'),
            'mainImageOn'           => config('entry_summary_image_on'),
            'detailDateOn'          => config('entry_summary_date'),
            'fullTextOn'            => config('entry_summary_fulltext'),
            'fulltextWidth'         => config('entry_summary_fulltext_width'),
            'fulltextMarker'        => config('entry_summary_fulltext_marker'),
            'tagOn'                 => config('entry_summary_tag'),
            'hiddenCurrentEntry'    => config('entry_summary_hidden_current_entry'),
            'hiddenPrivateEntry'    => config('entry_summary_hidden_private_entry'),
            'loop_class'            => config('entry_summary_loop_class'),
            'relational'            => config('entry_summary_relational'),
            'relationalType'        => config('entry_summary_relational_type')
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

        $this->setRelational();
        $this->buildModuleField($Tpl);

        $q = $this->buildQuery();
        $this->entries = $DB->query($q, 'all');
        foreach ($this->entries as $entry) {
            ACMS_RAM::entry($entry['entry_id'], $entry);
        }
        $this->buildSimplePager($Tpl);
        $this->buildEntries($Tpl);
        if ($this->buildNotFound($Tpl)) {
            return $Tpl->get();
        }
        if (empty($this->entries)) {
            return '';
        }
        $vars = $this->getRootVars();
        $vars += $this->buildFullspecPager($Tpl);
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
        $subCategory = isset($this->config['subCategory']) && $this->config['subCategory'] === 'on';

        $SQL1 = SQL::newSelect('entry', 'union1');
        $SQL1->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        $SQL1->addLeftJoin('category', 'entry_category_id', 'category_id');
        if ($subCategory) {
            $SQL1->addLeftJoin('entry_sub_category', 'entry_id', 'entry_sub_category_eid');
        }
        $SQL1->addLeftJoin('geo', 'entry_id', 'geo_eid');
        $this->filterCategoryFieldName = 'entry_category_id';
        $this->filterQuery($SQL1);
        $this->setSelect($SQL1);
        $q1 = $SQL1->get(dsn());

        if ($subCategory) {
            $SQL2 = SQL::newSelect('entry', 'union2');
            $SQL2->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
            $SQL2->addLeftJoin('entry_sub_category', 'entry_id', 'entry_sub_category_eid');
            $SQL2->addLeftJoin('category', 'entry_category_id', 'category_id');
            $SQL2->addLeftJoin('geo', 'entry_id', 'geo_eid');
            $this->filterCategoryFieldName = 'entry_sub_category_id';
            $this->filterQuery($SQL2);
            $this->setSelect($SQL2);
            $q2 = $SQL2->get(dsn());

            $sql = '((' . $q1 . ') UNION (' . $q2 . '))';
        } else {
            $sql = '(' . $q1 . ')';
        }
        $SQL = SQL::newSelect($sql, 'master');
        $this->setAmount($SQL); // limitする前のクエリから全件取得のクエリを準備しておく
        $this->orderQuery($SQL);
        $this->limitQuery($SQL);

        $SQL->setSelect(' *');
        $SQL->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        $SQL->addSelect('geo_geometry', 'longitude', null, POINT_X);

        return $SQL->get($this->dsn());
    }

    function setSelect(&$SQL)
    {
        $list = ['entry_id', 'entry_code', 'entry_status', 'entry_approval', 'entry_form_status', 'entry_sort', 'entry_user_sort', 'entry_category_sort', 'entry_title',
            'entry_link', 'entry_datetime', 'entry_start_datetime', 'entry_end_datetime', 'entry_posted_datetime', 'entry_updated_datetime', 'entry_summary_range', 'entry_indexing',
            'entry_members_only', 'entry_primary_image', 'entry_current_rev_id', 'entry_last_update_user_id', 'entry_category_id', 'entry_user_id', 'entry_form_id', 'entry_blog_id',
            'blog_id', 'blog_code', 'blog_status', 'blog_parent', 'blog_name', 'blog_domain', 'blog_indexing', 'blog_alias_status', 'blog_alias_sort', 'blog_alias_primary',
            'category_id', 'category_code', 'category_status', 'category_parent', 'category_sort', 'category_name', 'category_scope', 'category_indexing', 'category_blog_id', 'geo_geometry', 'geo_zoom'
        ];

        foreach ($list as $name) {
            $SQL->addSelect($name);
        }

        if (is_array($this->sortFields)) {
            foreach ($this->sortFields as $name) {
                $SQL->addSelect($name);
            }
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
        if (
            1
            and isset($this->config['relational']) && $this->config['relational'] === 'on'
            and is_array($this->eids)
            and count($this->eids) > 0
        ) {
            $SQL->addGroup('entry_id');
            $SQL->setFieldOrder('entry_id', $this->eids);
            return;
        }
        $sortFd = false;
        if (isset($this->config['noNarrowDownSort']) && $this->config['noNarrowDownSort'] === 'on') {
            // カテゴリー、ユーザー絞り込み時でも、絞り込み時用のソートを利用しない
            $sortFd = ACMS_Filter::entryOrder($SQL, $this->config['order'], null, null, false, $this->config['orderFieldName']);
        } else {
            $sortFd = ACMS_Filter::entryOrder($SQL, $this->config['order'], $this->uid, $this->cid, false, $this->config['orderFieldName']);
        }
        if ($sortFd) {
            $SQL->setGroup($sortFd);
        }
        $SQL->addGroup('entry_id');
    }

    /**
     * エントリー数取得sqlの準備
     *
     * @param SQL_Select $SQL
     * @return void
     */
    function setAmount($SQL)
    {
        $this->amount = new SQL_Select($SQL);
        $this->amount->addSelect('DISTINCT(entry_id)', 'entry_amount', null, 'COUNT');
    }

    /**
     * limitクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function limitQuery(&$SQL)
    {
        $from   = ($this->page - 1) * $this->config['limit'] + $this->config['offset'];
        $limit  = $this->config['limit'] + 1;
        $SQL->setLimit($limit, $from);
    }

    /**
     * 絞り込みクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function filterQuery(&$SQL)
    {
        $private = isset($this->config['hiddenPrivateEntry']) && $this->config['hiddenPrivateEntry'] === 'on';
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);
        ACMS_Filter::entrySession($SQL, null, $private);

        if ($this->relationalFilterQuery($SQL)) {
            return;
        }

        $multi = false;
        $multi = $this->categoryFilterQuery($SQL) || $multi;
        $multi = $this->userFilterQuery($SQL) || $multi;
        $multi = $this->entryFilterQuery($SQL) || $multi;
        $this->blogFilterQuery($SQL, $multi);

        $this->tagFilterQuery($SQL);
        $this->keywordFilterQuery($SQL);
        $this->fieldFilterQuery($SQL);

        $this->filterSubQuery($SQL);
        $this->otherFilterQuery($SQL);
    }

    /**
     * 関連エントリーの絞り込み
     *
     * @param SQL_Select & $SQL
     * @return bool
     */
    function relationalFilterQuery(&$SQL)
    {
        if (isset($this->config['relational']) && $this->config['relational'] === 'on') {
            $SQL->addWhereIn('entry_id', $this->eids);
            return true;
        }
        return false;
    }

    /**
     * カテゴリーの絞り込み
     *
     * @param SQL_Select & $SQL
     * @return bool
     */
    function categoryFilterQuery(&$SQL)
    {
        $multi = false;
        if (!empty($this->cid)) {
            $this->categorySubQuery = SQL::newSelect('category');
            $this->categorySubQuery->setSelect('category_id');
            if (is_int($this->cid)) {
                if ($this->categoryAxis() === 'self') {
                    $SQL->addWhereOpr($this->filterCategoryFieldName, $this->cid);
                } else {
                    ACMS_Filter::categoryTree($this->categorySubQuery, $this->cid, $this->categoryAxis());
                }
            } elseif (strpos($this->cid, ',') !== false) {
                $this->categorySubQuery->addWhereIn('category_id', explode(',', $this->cid));
                $multi = true;
            }
            if ($this->config['secret'] === 'on') {
                ACMS_Filter::categoryDisclosureSecretStatus($this->categorySubQuery);
            } else {
                ACMS_Filter::categoryStatus($this->categorySubQuery);
            }
        } else {
            if ('on' === $this->config['secret']) {
                ACMS_Filter::categoryDisclosureSecretStatus($SQL);
            } else {
                ACMS_Filter::categoryStatus($SQL);
            }
        }
        return $multi;
    }

    /**
     * ユーザーの絞り込み
     *
     * @param SQL_Select & $SQL
     * @return bool
     */
    function userFilterQuery(&$SQL)
    {
        $multi = false;
        if (!empty($this->uid)) {
            if (is_int($this->uid)) {
                $SQL->addWhereOpr('entry_user_id', $this->uid);
            } elseif (strpos($this->uid, ',') !== false) {
                $SQL->addWhereIn('entry_user_id', explode(',', $this->uid));
                $multi = true;
            }
        }
        return $multi;
    }

    /**
     * エントリーの絞り込み
     *
     * @param SQL_Select & $SQL
     * @return bool
     */
    function entryFilterQuery(&$SQL)
    {
        $multi = false;
        if (!empty($this->eid)) {
            if (is_int($this->eid)) {
                $SQL->addWhereOpr('entry_id', $this->eid);
            } elseif (strpos($this->eid, ',') !== false) {
                $SQL->addWhereIn('entry_id', explode(',', $this->eid));
                $multi = true;
            }
        }
        return $multi;
    }

    /**
     * ブログの絞り込み
     *
     * @param SQL_Select & $SQL
     * @param bool $multi
     * @return void
     */
    function blogFilterQuery(&$SQL, $multi)
    {
        if (!empty($this->bid) && is_int($this->bid) && $this->blogAxis() === 'self') {
            $SQL->addWhereOpr('entry_blog_id', $this->bid);
            if ('on' === $this->config['secret']) {
                ACMS_Filter::blogDisclosureSecretStatus($SQL);
            } else {
                ACMS_Filter::blogStatus($SQL);
            }
        } elseif (!empty($this->bid)) {
            $this->blogSubQuery = SQL::newSelect('blog');
            $this->blogSubQuery->setSelect('blog_id');
            if (is_int($this->bid)) {
                if ($multi) {
                    ACMS_Filter::blogTree($this->blogSubQuery, $this->bid, 'descendant-or-self');
                } else {
                    ACMS_Filter::blogTree($this->blogSubQuery, $this->bid, $this->blogAxis());
                }
            } else {
                if (strpos($this->bid, ',') !== false) {
                    $this->blogSubQuery->addWhereIn('blog_id', explode(',', $this->bid));
                }
            }
            if ('on' === $this->config['secret']) {
                ACMS_Filter::blogDisclosureSecretStatus($this->blogSubQuery);
            } else {
                ACMS_Filter::blogStatus($this->blogSubQuery);
            }
        }
    }

    /**
     * タグの絞り込み
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function tagFilterQuery(&$SQL)
    {
        if (!empty($this->tags)) {
            ACMS_Filter::entryTag($SQL, $this->tags);
        }
    }

    /**
     * キーワードの絞り込み
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function keywordFilterQuery(&$SQL)
    {
        if (!empty($this->keyword)) {
            ACMS_Filter::entryKeyword($SQL, $this->keyword);
        }
    }

    /**
     * フィールドの絞り込み
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function fieldFilterQuery(&$SQL)
    {
        if (!$this->Field->isNull()) {
            $this->sortFields = ACMS_Filter::entryField($SQL, $this->Field);
        }
    }

    /**
     * サブクエリの組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function filterSubQuery(&$SQL)
    {
        $DB = DB::singleton(dsn());
        if ($this->blogSubQuery) {
            $SQL->addWhereIn('entry_blog_id', $DB->subQuery($this->blogSubQuery));
        }
        if ($this->categorySubQuery) {
            $SQL->addWhereIn($this->filterCategoryFieldName, $DB->subQuery($this->categorySubQuery));
        } elseif (empty($this->cid) and null !== $this->cid) {
            $SQL->addWhereOpr('entry_category_id', null);
        }
    }

    /**
     * その他の絞り込み
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function otherFilterQuery(&$SQL)
    {
        if ('on' === $this->config['indexing']) {
            $SQL->addWhereOpr('entry_indexing', 'on');
        }
        if (isset($this->config['membersOnly']) && 'on' === $this->config['membersOnly']) {
            $SQL->addWhereOpr('entry_members_only', 'on');
        }
        if ('on' <> $this->config['noimage']) {
            $SQL->addWhereOpr('entry_primary_image', null, '<>');
        }
        if (EID && 'on' === $this->config['hiddenCurrentEntry']) {
            $SQL->addWhereOpr('entry_id', EID, '<>');
        }
    }

    /**
     * 関連エントリーの取得
     *
     * @return bool
     */
    function setRelational()
    {
        if (isset($this->config['relational']) && $this->config['relational'] === 'on') {
            if (!$this->eid && !EID) {
                return false;
            }
            $eid = $this->eid ? $this->eid : EID;
            $type = $this->config['relationalType'];
            if ($type) {
                $this->eids = loadRelatedEntries($eid, null, $type);
            } else {
                $this->eids = loadRelatedEntries($eid);
            }
        }
        return true;
    }

    /**
     * シンプルページャーの組み立て
     *
     * @param Template & $Tpl
     * @return void
     */
    function buildSimplePager(&$Tpl)
    {
        $next_page = false;
        if (count($this->entries) > $this->config['limit']) {
            array_pop($this->entries);
            $next_page = true;
        }
        if (!isset($this->config['simplePagerOn']) || $this->config['simplePagerOn'] !== 'on') {
            return;
        }
        // prev page
        if ($this->page > 1) {
            $Tpl->add('prevPage', [
                'url'    => acmsLink([
                    'page' => $this->page - 1,
                ], true),
            ]);
        } else {
            $Tpl->add('prevPageNotFound');
        }
        // next page
        if ($next_page) {
            $Tpl->add('nextPage', [
                'url'    => acmsLink([
                    'page' => $this->page + 1,
                ], true),
            ]);
        } else {
            $Tpl->add('nextPageNotFound');
        }
    }

    /**
     * コンフィグのセット
     *
     * @return bool
     */
    function setConfig()
    {
        $this->config = $this->initVars();
        if ($this->config === false) {
            return false;
        }
        return true;
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
        $eagerLoad = $this->eagerLoad();
        foreach ($this->entries as $i => $row) {
            $i++;
            $this->buildSummary($Tpl, $row, $i, $gluePoint, $this->config, [], $eagerLoad);
        }
    }

    /**
     * NotFound時のテンプレート組み立て
     *
     * @param Template & $Tpl
     * @return bool
     */
    function buildNotFound(&$Tpl)
    {
        if (!empty($this->entries)) {
            return false;
        }
        if ('on' !== $this->config['notfound']) {
            return false;
        }

        $Tpl->add('notFound');
        $Tpl->add(null, $this->getRootVars());
        if (isset($this->config['notfoundStatus404']) && 'on' === $this->config['notfoundStatus404']) {
            httpStatusCode('404 Not Found');
        }
        return true;
    }

    /**
     * ルート変数の取得
     *
     * @return array
     */
    function getRootVars()
    {
        $blogName   = ACMS_RAM::blogName($this->bid);
        $vars = [
            'indexUrl'  => acmsLink([
                'bid'   => $this->bid,
                'cid'   => $this->cid,
            ]),
            'indexBlogName' => $blogName,
            'blogName'      => $blogName,
            'blogCode'      => ACMS_RAM::blogCode($this->bid),
            'blogUrl'       => acmsLink([
                'bid'   => $this->bid,
            ]),
        ];
        if (!empty($this->cid)) {
            $categoryName   = ACMS_RAM::categoryName($this->cid);
            $vars['indexCategoryName']  = $categoryName;
            $vars['categoryName']       = $categoryName;
            $vars['categoryCode']       = ACMS_RAM::categoryCode($this->cid);
            $vars['categoryUrl']        = acmsLink([
                'bid'   => $this->bid,
                'cid'   => $this->cid,
            ]);
        }
        return $vars;
    }

    /**
     * フルスペックページャーの組み立て
     *
     * @param Template & $Tpl
     * @return array
     */
    function buildFullspecPager(&$Tpl)
    {
        $vars = [];
        if (isset($this->config['order'][0]) && 'random' === $this->config['order'][0]) {
            return $vars;
        }
        if (!isset($this->config['pagerOn']) || $this->config['pagerOn'] !== 'on') {
            return $vars;
        }
        $itemsAmount = intval(DB::query($this->amount->get($this->dsn()), 'one'));
        $itemsAmount -= $this->config['offset'];
        $vars += $this->buildPager($this->page, $this->config['limit'], $itemsAmount, $this->config['pagerDelta'], $this->config['pagerCurAttr'], $Tpl);

        return $vars;
    }

    /**
     * EagerLoading
     *
     * @return array
     */
    protected function eagerLoad()
    {
        $eagerLoadingData = [];
        $entryIds = [];
        $userIds = [];
        $blogIds = [];
        $categoryIds = [];
        foreach ($this->entries as $entry) {
            if (!empty($entry['entry_id'])) {
                $entryIds[] = $entry['entry_id'];
            }
            if (!empty($entry['entry_user_id'])) {
                $userIds[] = $entry['entry_user_id'];
            }
            if (!empty($entry['entry_blog_id'])) {
                $blogIds[] = $entry['entry_blog_id'];
            }
            if (!empty($entry['entry_category_id'])) {
                $categoryIds[] = $entry['entry_category_id'];
            }
        }
        // メイン画像のEagerLoading
        if ($data = $this->mainImageEagerLoad()) {
            $eagerLoadingData['mainImage'] = $data;
        }
        // フルテキストのEagerLoading
        if ($data = $this->fullTextEagerLoad()) {
            $eagerLoadingData['fullText'] = $data;
        }
        // タグのEagerLoading
        if ($data = $this->tagEagerLoad()) {
            $eagerLoadingData['tag'] = $data;
        }
        // エントリーフィールドのEagerLoading
        if ($data = $this->entryFieldEagerLoad()) {
            $eagerLoadingData['entryField'] = $data;
        }
        // ユーザーフィールドのEagerLoading
        if ($data = $this->userFieldEagerLoad()) {
            $eagerLoadingData['userField'] = $data;
        }
        // ブログフィールドのEagerLoading
        if ($data = $this->blogFieldEagerLoad()) {
            $eagerLoadingData['blogField'] = $data;
        }
        // カテゴリーフィールドのEagerLoading
        if ($data = $this->categoryFieldEagerLoad()) {
            $eagerLoadingData['categoryField'] = $data;
        }
        // サブカテゴリーのEagerLoading
        if ($data = $this->subCategoryFieldEagerLoad()) {
            $eagerLoadingData['subCategory'] = $data;
        }
        // 関連エントリーのEagerLoading
        if ($data = $this->relatedEntryEagerLoad()) {
            $eagerLoadingData['relatedEntry'] = $data;
        }
        return $eagerLoadingData;
    }

    /**
     * メイン画像のEagerLoading
     *
     * @return array|bool
     */
    protected function mainImageEagerLoad()
    {
        if (!isset($this->config['mainImageOn']) || $this->config['mainImageOn'] === 'on') {
            return Tpl::eagerLoadMainImage($this->entries);
        }
        return false;
    }

    /**
     * フルテキストのEagerLoading
     *
     * @return array|bool
     */
    protected function fullTextEagerLoad()
    {
        if (!isset($this->config['fullTextOn']) || $this->config['fullTextOn'] === 'on') {
            $entryIds = [];
            foreach ($this->entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            return Tpl::eagerLoadFullText($entryIds);
        }
        return false;
    }

    /**
     * タグのEagerLoading
     *
     * @return array|bool
     */
    protected function tagEagerLoad()
    {
        if (isset($this->config['tagOn']) && $this->config['tagOn'] === 'on') {
            $entryIds = [];
            foreach ($this->entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            return Tpl::eagerLoadTag($entryIds);
        }
        return false;
    }

    /**
     * エントリーフィールドのEagerLoading
     *
     * @return array|bool
     */
    protected function entryFieldEagerLoad()
    {
        if (!isset($this->config['entryFieldOn']) || $this->config['entryFieldOn'] === 'on') {
            $entryIds = [];
            foreach ($this->entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            return eagerLoadField($entryIds, 'eid');
        }
        return false;
    }

    /**
     * 関連エントリーのEagerLoading
     *
     * @return array|bool
     */
    protected function relatedEntryEagerLoad()
    {
        if (isset($this->config['relatedEntryOn']) && $this->config['relatedEntryOn'] === 'on') {
            $entryIds = [];
            foreach ($this->entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            return Tpl::eagerLoadRelatedEntry($entryIds);
        }
        return false;
    }

    /**
     * ユーザーフィールドのEagerLoading
     *
     * @return array|bool
     */
    protected function userFieldEagerLoad()
    {
        if (isset($this->config['userInfoOn']) && $this->config['userInfoOn'] === 'on') {
            $userIds = [];
            foreach ($this->entries as $entry) {
                if (!empty($entry['entry_user_id'])) {
                    $userIds[] = $entry['entry_user_id'];
                }
            }
            return eagerLoadField($userIds, 'uid');
        }
        return false;
    }

    /**
     * ブログフィールドのEagerLoading
     *
     * @return array|bool
     */
    protected function blogFieldEagerLoad()
    {
        if (isset($this->config['blogInfoOn']) && $this->config['blogInfoOn'] === 'on') {
            $blogIds = [];
            foreach ($this->entries as $entry) {
                if (!empty($entry['entry_blog_id'])) {
                    $blogIds[] = $entry['entry_blog_id'];
                }
            }
            return eagerLoadField($blogIds, 'bid');
        }
        return false;
    }

    /**
     * カテゴリーフィールドのEagerLoading
     *
     * @return array|bool
     */
    protected function categoryFieldEagerLoad()
    {
        if (isset($this->config['categoryInfoOn']) && $this->config['categoryInfoOn'] === 'on') {
            $categoryIds = [];
            foreach ($this->entries as $entry) {
                if (!empty($entry['entry_category_id'])) {
                    $categoryIds[] = $entry['entry_category_id'];
                }
            }
            return eagerLoadField($categoryIds, 'cid');
        }
        return false;
    }

    /**
     * サブカテゴリーのEagerLoading
     *
     * @return array|bool
     */
    protected function subCategoryFieldEagerLoad()
    {
        if (isset($this->config['categoryInfoOn']) && $this->config['categoryInfoOn'] === 'on') {
            $entryIds = [];
            foreach ($this->entries as $entry) {
                if (!empty($entry['entry_id'])) {
                    $entryIds[] = $entry['entry_id'];
                }
            }
            return eagerLoadSubCategories($entryIds);
        }
        return false;
    }

    /**
     * @return array
     */
    protected function dsn()
    {
        return dsn(['prefix' => '']);
    }
}
