<?php

use Acms\Services\Entry\Exceptions\NotFoundException;
use Acms\Services\Facades\Template as TplHelper;

class ACMS_GET_Entry_Body extends ACMS_GET_Entry
{
    /**
     * 階層設定
     * @inheritDoc
     */
    public $_axis = [ // phpcs:ignore
        'bid' => 'descendant-or-self',
        'cid' => 'descendant-or-self',
    ];

    /**
     * スコープ設定
     * @inheritDoc
     */
    public $_scope = [ // phpcs:ignore
        'uid' => 'global',
        'cid' => 'global',
        'eid' => 'global',
        'keyword' => 'global',
        'tag' => 'global',
        'field' => 'global',
        'date' => 'global',
        'start' => 'global',
        'end' => 'global',
        'page' => 'global',
        'order' => 'global',
    ];

    /**
     * コンフィグの設定値
     *
     * @var array
     */
    protected $config = [];

    /**
     * フルテキストのEagerLoadingData
     *
     * @var array
     */
    protected $summaryFulltextEagerLoadingData = [];

    /**
     * メインイメージのEagerLoadingData
     *
     * @var array
     */
    protected $mainImageEagerLoadingData = [];

    /**
     * 関連エントリーのEagerLoadingData
     *
     * @var array
     */
    protected $relatedEntryEagerLoadingData = [];

    /**
     * マイクロページのページ数
     *
     * @var int
     */
    protected $micropage = 0;

    /**
     * マイクロページの改ページ場所（ユニット数）
     *
     * @var int
     */
    protected $micropageBreak = 0;

    /**
     * マイクロページのラベル
     *
     * @var array
     */
    protected $micropageLabel = [];

    /**
     * 会員限定記事
     *
     * @var bool
     */
    protected $membersOnly = false;

    /**
     * コンフィグのセット
     *
     * @return bool
     */
    function setConfig(): bool
    {
        $this->config = $this->initConfig();
        if ($this->config === false) {
            return false;
        }
        return true;
    }

    /**
     * コンフィグのロード
     *
     * @return array
     */
    function initConfig(): array
    {
        return [
            'order' => [
                $this->order ? $this->order : config('entry_body_order'),
                config('entry_body_order2'),
            ],
            'limit' => config('entry_body_limit'),
            'offset' => config('entry_body_offset'),
            'image_viewer' => config('entry_body_image_viewer'),
            'indexing' => config('entry_body_indexing'),
            'members_only' => config('entry_summary_members_only'),
            'sub_category' => config('entry_body_sub_category'),
            'newtime' => config('entry_body_newtime'),
            'serial_navi_ignore_category_on' => config('entry_body_serial_navi_ignore_category'),
            'tag_on' => config('entry_body_tag_on'),
            'summary_on' => config('entry_body_summary_on'),
            'related_entry_on' => config('entry_body_related_entry_on', 'off'),
            'show_all_index' => config('entry_body_show_all_index'),
            'date_on' => config('entry_body_date_on'),
            'detail_date_on' => config('entry_body_detail_date_on'),
            'comment_on' => config('entry_body_comment_on'),
            'geolocation_on' => config('entry_body_geolocation_on'),
            'trackback_on' => config('entry_body_trackback_on'),
            'serial_navi_on' => config('entry_body_serial_navi_on'),
            'simple_pager_on' => config('entry_body_simple_pager_on'),
            'category_order' => config('entry_body_category_order'),
            'notfoundStatus404' => config('entry_body_notfound_status_404'),
            'micropager_on' => config('entry_body_micropage'),
            'micropager_delta' => config('entry_body_micropager_delta'),
            'micropager_cur_attr' => config('entry_body_micropager_cur_attr'),
            'pager_on' => config('entry_body_pager_on'),
            'pager_delta' => config('entry_body_pager_delta'),
            'pager_cur_attr' => config('entry_body_pager_cur_attr'),
            'entry_field_on' => config('entry_body_entry_field_on'),
            'user_field_on' => config('entry_body_user_field_on'),
            'category_field_on' => config('entry_body_category_field_on'),
            'blog_field_on' => config('entry_body_blog_field_on'),
            'user_info_on' => config('entry_body_user_info_on'),
            'category_info_on' => config('entry_body_category_info_on'),
            'blog_info_on' => config('entry_body_blog_info_on'),
            'loop_class' => config('entry_body_loop_class'),
        ];
    }

    /**
     * Main
     */
    function get()
    {
        if (!$this->setConfig()) {
            return '';
        }

        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($tpl);

        if (in_array(ADMIN, ['entry-edit', 'entry_editor'], true)) {
            // 編集ページ
            $this->editPage($tpl);
        } elseif (ADMIN === 'form2-edit') {
            // 動的フォーム編集ページ
            $this->formEditPage($tpl);
        } elseif (intval($this->eid) > 0 && strval($this->eid) === strval(intval($this->eid))) {
            // エントリー詳細ページ
            try {
                $this->entryPage($tpl);
            } catch (NotFoundException $e) {
                return $this->resultsNotFound($tpl);
            }
        } else {
            // エントリー一覧ページ
            try {
                $this->entryIndex($tpl);
            } catch (NotFoundException $e) {
                return $this->resultsNotFound($tpl);
            }
        }

        return $tpl->get();
    }

    /**
     * エントリー編集ページ
     *
     * @param Template $tpl
     * @return void
     */
    protected function editPage(Template $tpl): void
    {
        $vars = [];
        if (ADMIN === 'entry_editor' && EID) {
            $tpl->add(['revision', 'entry:loop']);
        }
        $action = !EID ? 'insert' : 'update';
        $tpl->add(['header#' . $action, 'adminEntryTitle']);
        $tpl->add(['description#' . $action, 'adminEntryTitle']);
        $tpl->add(['adminEntryTitle', 'adminEntryEdit', 'entry:loop']);
        $tpl->add(['adminEntryEdit', 'entry:loop']);
        $tpl->add('entry:loop', $vars);
    }

    /**
     * 動的フォーム編集ページ
     *
     * @param Template $tpl
     * @return void
     */
    protected function formEditPage(Template $tpl): void
    {
        $tpl->add(['adminFormEdit', 'entry:loop']);
        $tpl->add('entry:loop');
    }

    /**
     * エントリー詳細ページ
     *
     * @param Template $tpl
     * @return void
     */
    protected function entryPage(Template $tpl): void
    {
        if (RVID) {
            $sql = SQL::newSelect('entry_rev');
            $sql->addWhereOpr('entry_rev_id', RVID);
        } else {
            $sql = SQL::newSelect('entry');
        }
        $sql->addWhereOpr('entry_id', $this->eid);
        /**
         * @var false|array{
         *   entry_id: int,
         *   entry_title: string,
         *   entry_members_only: string,
         * } $entry
         */
        $entry = DB::query($sql->get(dsn()), 'row');

        if (empty($entry)) {
            throw new NotFoundException();
        }
        $entry['entry_title'] = $this->getFixTitle($entry['entry_title']);
        $eid = intval($entry['entry_id']);
        $this->membersOnly = $entry['entry_members_only'] === 'on';
        $vars = [];

        // ユニットを組み立て
        $this->buildEntryUnit($tpl, $entry);

        // フルテキストのEagerLoading
        if ($this->config['summary_on'] === 'on') {
            $this->summaryFulltextEagerLoadingData = TplHelper::eagerLoadFullText([$eid]);
        }
        // メインイメージのEagerLoading
        if (config('entry_body_image_on') === 'on') {
            $this->mainImageEagerLoadingData = TplHelper::eagerLoadMainImage([$entry]);
        }
        // 関連エントリーのEagerLoading
        if ($this->config['related_entry_on'] === 'on') {
            $this->relatedEntryEagerLoadingData = TplHelper::eagerLoadRelatedEntry([$eid]);
        }
        // テンプレートを組み立て
        $this->buildBodyField($tpl, $vars, $entry);

        // 動的フォームを表示・非表示
        $this->buildFormBody($tpl, $entry);

        // エントリー一件を表示
        $tpl->add('entry:loop', $vars);

        // 前後リンクを組み立て
        $this->buildSerialNavi($tpl, $entry);

        // マイクロページを組み立て
        $this->buildMicroPage($tpl);

        $tpl->add(null, [
            'upperUrl' => acmsLink([
                'eid' => false,
            ]),
        ]);
    }

    /**
     * エントリー一覧ページ
     *
     * @param Template $tpl
     * @return void
     * @throws NotFoundException
     */
    protected function entryIndex(Template $tpl): void
    {
        $limit = idval($this->config['limit']);
        $from = ($this->page - 1) * $limit;

        $sql = $this->buildIndexQuery();

        $entryAmountSql = new SQL_Select($sql);
        $entryAmountSql->setSelect('DISTINCT(entry_id)', 'entry_amount', null, 'COUNT');

        $offset = intval($this->config['offset']);
        $_limit = $limit + 1;
        $sortFd = ACMS_Filter::entryOrder($sql, $this->config['order'], $this->uid, $this->cid);
        $sql->setLimit($_limit, $from + $offset);
        if (!empty($sortFd)) {
            $sql->setGroup($sortFd);
        }
        $sql->addGroup('entry_id');

        $q = $sql->get(dsn());
        $entries = DB::query($q, 'all');

        $nextPage = false;
        if (count($entries) > $limit) {
            array_pop($entries);
            $nextPage = true;
        }
        $this->buildEntryIndex($tpl, $entries, $nextPage, $entryAmountSql);
    }

    /**
     * エントリー一覧を組み立て
     *
     * @param Template $tpl
     * @param array $entries
     * @param bool $nextPage
     * @param SQL_Select $entryAmountSql
     * @return void
     * @throws NotFoundException
     */
    protected function buildEntryIndex(Template $tpl, array $entries, bool $nextPage, SQL_Select $entryAmountSql): void
    {
        // not Found
        if (empty($entries)) {
            throw new NotFoundException();
        }
        // simple pager
        if ($this->config['simple_pager_on'] === 'on') {
            // prev page
            if ($this->page > 1) {
                $tpl->add('prevPage', [
                    'url' => acmsLink([
                        'page' => $this->page - 1,
                    ], true),
                ]);
            } else {
                $tpl->add('prevPageNotFound');
            }
            // next page
            if ($nextPage) {
                $tpl->add('nextPage', [
                    'url' => acmsLink([
                        'page' => $this->page + 1,
                    ], true),
                ]);
            } else {
                $tpl->add('nextPageNotFound');
            }
        }
        $entryIds = [];
        foreach ($entries as $entry) {
            $entryIds[] = $entry['entry_id'];
        }

        // フルテキストのEagerLoading
        if ($this->config['summary_on'] === 'on') {
            $this->summaryFulltextEagerLoadingData = Tpl::eagerLoadFullText($entryIds);
        }
        // メインイメージのEagerLoading
        if (config('entry_body_image_on') === 'on') {
            $this->mainImageEagerLoadingData = Tpl::eagerLoadMainImage($entries);
        }
        // 関連エントリーのEagerLoading
        if ($this->config['related_entry_on'] === 'on') {
            $this->relatedEntryEagerLoadingData = Tpl::eagerLoadRelatedEntry($entryIds);
        }
        // build summary tpl
        foreach ($entries as $i => $row) {
            $serial = ++$i;
            $eid = intval($row['entry_id']);
            $row['entry_title'] = $this->getFixTitle($row['entry_title']);
            $continueName = $row['entry_title'];
            $summaryRange = strval(config('entry_body_fix_summary_range'));
            if (!strlen($summaryRange)) {
                $summaryRange = strval($row['entry_summary_range']);
            }
            $summaryRange = !!strlen($summaryRange) ? intval($summaryRange) : null;
            $inheritUrl = acmsLink([
                'bid' => $row['entry_blog_id'],
                'eid' => $eid,
            ]);

            $vars = [];
            $rvid_ = RVID;
            if (!RVID && $row['entry_approval'] === 'pre_approval') {
                $rvid_  = 1;
            }
            // column
            if ($this->config['show_all_index'] === 'on') {
                $summaryRange = null;
            }
            $sql = SQL::newSelect('column');
            $sql->addSelect('*', 'column_amount', null, 'COUNT');
            $sql->addWhereOpr('column_entry_id', $eid);
            $amount = DB::query($sql->get(dsn()), 'one');

            if ($units = loadColumn($eid, $summaryRange, $rvid_)) {
                $this->buildColumn($units, $tpl, $eid);
                if (!empty($summaryRange)) {
                    if ($summaryRange < $amount) {
                        $vars['continueUrl'] = $inheritUrl;
                        $vars['continueName'] = $continueName;
                    }
                }
            } elseif ($amount > 0) {
                $vars['continueUrl'] = $inheritUrl;
                $vars['continueName'] = $continueName;
            }
            $this->buildBodyField($tpl, $vars, $row, $serial);
            $tpl->add('entry:loop', $vars);
        }

        $rootVars = [];
        if ('random' <> strtolower($this->config['order'][0]) && ($this->config['pager_on'] === 'on')) {
            $itemsAmount = intval(DB::query($entryAmountSql->get(dsn()), 'one'));

            $delta = intval($this->config['pager_delta']);
            $curAttr = $this->config['pager_cur_attr'];
            if (is_numeric($this->config['offset'])) {
                $itemsAmount -= $this->config['offset'];
            }
            $limit = idval($this->config['limit']);
            $rootVars += $this->buildPager($this->page, $limit, $itemsAmount, $delta, $curAttr, $tpl);
        }
        $tpl->add(null, $rootVars);
    }

    /**
     * エントリー一覧のクエリを組み立て
     *
     * @return SQL_Select
     */
    protected function buildIndexQuery(): SQL_Select
    {
        $multiId = false;
        $blogSub = null;
        $categorySub = null;

        $sql = SQL::newSelect('entry');
        $sql->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        $sql->addLeftJoin('category', 'category_id', 'entry_category_id');
        if (!empty($this->cid)) {
            $categorySub = SQL::newSelect('category');
            $categorySub->setSelect('category_id');
            if (is_int($this->cid)) {
                ACMS_Filter::categoryTree($categorySub, $this->cid, $this->categoryAxis());
            } elseif (strpos($this->cid, ',') !== false) {
                $categorySub->addWhereIn('category_id', explode(',', $this->cid));
                $multiId = true;
            }
            ACMS_Filter::categoryStatus($categorySub);
        } else {
            ACMS_Filter::categoryStatus($sql);
        }
        ACMS_Filter::entrySpan($sql, $this->start, $this->end);
        ACMS_Filter::entrySession($sql);
        if (!empty($this->Field)) {
            ACMS_Filter::entryField($sql, $this->Field);
        }
        if (!empty($this->tags)) {
            ACMS_Filter::entryTag($sql, $this->tags);
        }
        if (!empty($this->keyword)) {
            ACMS_Filter::entryKeyword($sql, $this->keyword);
        }
        if ($this->config['indexing'] === 'on') {
            $sql->addWhereOpr('entry_indexing', 'on');
        }
        if ($this->config['members_only'] === 'on') {
            $sql->addWhereOpr('entry_members_only', 'on');
        }
        if (!empty($this->uid)) {
            if (is_int($this->uid)) {
                $sql->addWhereOpr('entry_user_id', $this->uid);
            } elseif (strpos($this->uid, ',') !== false) {
                $sql->addWhereIn('entry_user_id', explode(',', $this->uid));
                $multiId = true;
            }
        }
        if (!empty($this->eid) && !is_int($this->eid)) {
            $sql->addWhereIn('entry_id', explode(',', $this->eid));
            $multiId = true;
        }
        if (!empty($this->bid)) {
            $blogSub = SQL::newSelect('blog');
            $blogSub->setSelect('blog_id');
            if (is_int($this->bid)) {
                if ($multiId) {
                    ACMS_Filter::blogTree($blogSub, $this->bid, 'descendant-or-self');
                } else {
                    ACMS_Filter::blogTree($blogSub, $this->bid, $this->blogAxis());
                }
            } elseif (strpos($this->bid, ',') !== false) {
                $blogSub->addWhereIn('blog_id', explode(',', $this->bid));
            }
            ACMS_Filter::blogStatus($blogSub);
        } else {
            ACMS_Filter::blogStatus($sql);
        }
        if ($blogSub) {
            $sql->addWhereIn('entry_blog_id', DB::subQuery($blogSub));
        }
        if ($categorySub) {
            if ($this->config['sub_category'] === 'on') {
                $sub = SQL::newWhere();
                $sub->addWhereIn('entry_category_id', DB::subQuery($categorySub), 'OR');

                $sub2 = SQL::newSelect('entry_sub_category');
                $sub2->addSelect('entry_sub_category_eid');
                $sub2->addWhereIn('entry_sub_category_id', DB::subQuery($categorySub));
                $sub->addWhereIn('entry_id', DB::subQuery($sub2), 'OR');

                $sql->addWhere($sub);
            } else {
                $sql->addWhereIn('entry_category_id', DB::subQuery($categorySub));
            }
        }
        return $sql;
    }

    /**
     * 動的フォームの表示・非表示
     *
     * @param Template $tpl
     * @param array $entry
     * @return void
     */
    protected function buildFormBody(Template $tpl, array $entry): void
    {
        if (
            1
            && isset($entry['entry_form_id'])
            && !empty($entry['entry_form_id'])
            && isset($entry['entry_form_status'])
            && $entry['entry_form_status'] === 'open'
            && config('form_edit_action_direct') == 'on'
        ) {
            $tpl->add('formBody');
        }
    }

    /**
     * 修正したエントリータイトルを取得
     *
     * @param string $title
     * @return string
     */
    protected function getFixTitle(string $title): string
    {
        if (!IS_LICENSED) {
            return '[test]' . $title;
        }
        return $title;
    }

    /**
     * ユニットを組み立て
     *
     * @param Template $tpl
     * @param array $entry
     * @return void
     */
    protected function buildEntryUnit(Template $tpl, array $entry): void
    {
        $eid = intval($entry['entry_id']);
        $RVID_ = RVID;
        if (!RVID && $entry['entry_approval'] === 'pre_approval') {
            $RVID_ = 1;
        }

        $summaryRange = null;
        if ($this->membersOnly) {
            // 会員限定記事対応
            $summaryRange = strval($entry['entry_summary_range']);
            $summaryRange = !!strlen($summaryRange) ? intval($summaryRange) : null;
            $tpl->add(['membersOnly', 'entry:loop']);

            if ($summaryRange !== null) {
                $allColumn = loadColumn($eid, null, $RVID_);
                $page = 1;
                $this->micropageBreak = 1;
                foreach ($allColumn as $i => $col) {
                    if ('break' == $col['type']) {
                        if ($i < $summaryRange) {
                            $page += 1;
                        }
                        $this->micropageBreak += 1;
                    }
                }
                if ($summaryRange < count($allColumn) && $page <= intval($this->page)) {
                    $tpl->add(['continueLink', 'entry:loop'], [
                        'dummy' => 'dummy',
                    ]);
                }
            }
        }
        if ($units = loadColumn($eid, $summaryRange, $RVID_)) {
            if ($this->config['micropager_on'] === 'on') {
                // マイクロページャー有効時
                $micropageBreakPage = 1;
                $this->micropage = intval($this->page);
                $_units = $units;
                $units = [];
                foreach ($_units as $unit) {
                    if ('break' == $unit['type']) {
                        if ($this->micropage === $micropageBreakPage) {
                            buildUnitData($unit['label'], $this->micropageLabel, 'label');
                        }
                        $micropageBreakPage += 1;
                    }
                    if ($this->micropage === $micropageBreakPage) {
                        $units[] = $unit;
                    }
                }
            }
            $this->buildColumn($units, $tpl, $eid);
        } else {
            // ユニットがない場合
            $tpl->add('unit:loop');
            if (
                1
                && VIEW === 'entry'
                && 'on' === config('entry_edit_inplace_enable')
                && 'on' === config('entry_edit_inplace')
                && (!enableApproval() || sessionWithApprovalAdministrator())
                && $entry['entry_approval'] !== 'pre_approval'
                && !ADMIN
                && (0
                    || roleEntryAuthorization(BID, $entry, false)
                    || ( 1
                        && sessionWithContribution()
                        && SUID == ACMS_RAM::entryUser($eid)
                    )
                )
            ) {
                $tpl->add('edit_inplace', [
                    'class' => 'js-edit_inplace'
                ]);
            }
        }
    }

    /**
     * 一個前のリンクを組み立て
     *
     * @param Template $tpl
     * @param string $sortFieldName
     * @param string $sortOrder
     * @param SQL_Select $baseSql
     * @param string $field
     * @param string $value
     * @return void
     */
    protected function buildPrevLink(Template $tpl, string $sortFieldName, string $sortOrder, SQL_Select $baseSql, string $field, string $value): void
    {
        $sql = new SQL_Select($baseSql);
        $where1 = SQL::newWhere();
        $where1->addWhereOpr($field, $value, '=');
        $where1->addWhereOpr('entry_id', $this->eid, '<');
        $where2 = SQL::newWhere();
        $where2->addWhere($where1);
        if ($sortOrder === 'desc') {
            $where2->addWhereOpr($field, $value, '<', 'OR');
        } else {
            $where2->addWhereOpr($field, $value, '>', 'OR');
        }
        $sql->addWhere($where2);
        if ($sortOrder === 'desc') {
            ACMS_Filter::entryOrder($sql, [$sortFieldName . '-desc', 'id-desc'], $this->uid, $this->cid);
        } else {
            ACMS_Filter::entryOrder($sql, [$sortFieldName . '-asc', 'id-asc'], $this->uid, $this->cid);
        }
        ACMS_Filter::entrySession($sql);
        $q = $sql->get(dsn());

        if ($row = DB::query($q, 'row')) {
            $tpl->add('prevLink', [
                'name' => addPrefixEntryTitle(
                    $row['entry_title'],
                    $row['entry_status'],
                    $row['entry_start_datetime'],
                    $row['entry_end_datetime'],
                    $row['entry_approval']
                ),
                'url' => acmsLink([
                    '_inherit' => true,
                    'eid' => $row['entry_id'],
                ]),
                'eid' => $row['entry_id'],
            ]);
        } else {
            $tpl->add('prevNotFound');
        }
    }

    /**
     * 次のリンクを組み立て
     *
     * @param Template $tpl
     * @param string $sortFieldName
     * @param string $sortOrder
     * @param SQL_Select $baseSql
     * @param string $field
     * @param string $value
     * @return void
     */
    protected function buildNextLink(Template $tpl, string $sortFieldName, string $sortOrder, SQL_Select $baseSql, string $field, string $value): void
    {
        $sql = new SQL_Select($baseSql);
        $where1 = SQL::newWhere();
        $where1->addWhereOpr($field, $value, '=');
        $where1->addWhereOpr('entry_id', $this->eid, '>');
        $where2 = SQL::newWhere();
        $where2->addWhere($where1);
        if ($sortOrder === 'desc') {
            $where2->addWhereOpr($field, $value, '>', 'OR');
        } else {
            $where2->addWhereOpr($field, $value, '<', 'OR');
        }
        $sql->addWhere($where2);
        if ($sortOrder === 'desc') {
            ACMS_Filter::entryOrder($sql, [$sortFieldName . '-asc', 'id-asc'], $this->uid, $this->cid);
        } else {
            ACMS_Filter::entryOrder($sql, [$sortFieldName . '-desc', 'id-desc'], $this->uid, $this->cid);
        }
        ACMS_Filter::entrySession($sql);
        $q = $sql->get(dsn());

        if ($row = DB::query($q, 'row')) {
            $tpl->add('nextLink', [
                'name' => addPrefixEntryTitle(
                    $row['entry_title'],
                    $row['entry_status'],
                    $row['entry_start_datetime'],
                    $row['entry_end_datetime'],
                    $row['entry_approval']
                ),
                'url' => acmsLink([
                    '_inherit' => true,
                    'eid' => $row['entry_id'],
                ]),
                'eid' => $row['entry_id'],
            ]);
        } else {
            $tpl->add('nextNotFound');
        }
    }

    /**
     * 前後リンクを組み立て
     *
     * @param Template $tpl
     * @param array $entry
     * @return void
     */
    protected function buildSerialNavi(Template $tpl, array $entry): void
    {
        if ($this->config['serial_navi_on'] !== 'on') {
            return;
        }
        $sql = SQL::newSelect('entry');
        $sql->addLeftJoin('category', 'category_id', 'entry_category_id');
        $sql->setLimit(1);
        $sql->addWhereOpr('entry_link', ''); // リンク先URLが設定されているエントリーはリンクに含まないようにする
        $sql->addWhereOpr('entry_blog_id', $this->bid);
        if ($this->config['serial_navi_ignore_category_on'] !== 'on') {
            ACMS_Filter::categoryTree($sql, $this->cid, $this->categoryAxis());
        }
        ACMS_Filter::entrySession($sql);
        ACMS_Filter::entrySpan($sql, $this->start, $this->end);
        if (!empty($this->tags)) {
            ACMS_Filter::entryTag($sql, $this->tags);
        }
        if (!empty($this->keyword)) {
            ACMS_Filter::entryKeyword($sql, $this->keyword);
        }
        if (!empty($this->Field)) {
            ACMS_Filter::entryField($sql, $this->Field);
        }
        $sql->addWhereOpr('entry_indexing', 'on');
        $aryOrder1 = explode('-', $this->config['order'][0]);
        $sortFieldName = isset($aryOrder1[0]) ? $aryOrder1[0] : null;
        $sortOrder = isset($aryOrder1[1]) ? $aryOrder1[1] : 'desc';

        if ('random' <> $sortFieldName) {
            switch ($sortFieldName) {
                case 'datetime':
                    $field = 'entry_datetime';
                    $value = ACMS_RAM::entryDatetime($this->eid);
                    break;
                case 'updated_datetime':
                    $field = 'entry_updated_datetime';
                    $value = ACMS_RAM::entryUpdatedDatetime($this->eid);
                    break;
                case 'posted_datetime':
                    $field = 'entry_posted_datetime';
                    $value = ACMS_RAM::entryPostedDatetime($this->eid);
                    break;
                case 'code':
                    $field = 'entry_code';
                    $value = ACMS_RAM::entryCode($this->eid);
                    break;
                case 'sort':
                    if ($this->uid) {
                        $field = 'entry_user_sort';
                        $value = ACMS_RAM::entryUserSort($this->eid);
                    } elseif ($this->cid) {
                        $field = 'entry_category_sort';
                        $value = ACMS_RAM::entryCategorySort($this->eid);
                    } else {
                        $field = 'entry_sort';
                        $value = ACMS_RAM::entrySort($this->eid);
                    }
                    break;
                case 'id':
                default:
                    $field = 'entry_id';
                    $value = $this->eid;
            }

            // build prevLink
            $this->buildPrevLink($tpl, $sortFieldName, $sortOrder, $sql, $field, $value);

            // build nextLink
            $this->buildNextLink($tpl, $sortFieldName, $sortOrder, $sql, $field, $value);
        }
    }

    /**
     * マイクロページネーションを組み立て
     *
     * @param Template $tpl
     * @return void
     */
    protected function buildMicroPage(Template $tpl): void
    {
        if ($this->config['micropager_on'] !== 'on') {
            return;
        }
        if ($this->micropageBreak < 1) {
            return;
        }
        // micropage
        if (!empty($this->micropageLabel)) {
            $this->micropageLabel['url'] = acmsLink([
                '_inherit' => true,
                'eid' => $this->eid,
                'page' => $this->micropage + 1,
            ]);
            $tpl->add('micropageLink', $this->micropageLabel);
        }

        // micropager
        if (!empty($this->micropage)) {
            $vars = [];
            $delta = $this->config['micropager_delta'];
            $curAttr = $this->config['micropager_cur_attr'];
            $vars += $this->buildPager($this->micropage, 1, $this->micropageBreak, $delta, $curAttr, $tpl, 'micropager');
            $tpl->add('micropager', $vars);
        }
    }

    /**
     * カテゴリーを組み立て
     *
     * @param Template $tpl
     * @param int $cid
     * @param int $bid
     * @return void
     */
    function buildCategory(Template $tpl, int $cid, int $bid): void
    {
        $sql = SQL::newSelect('category');
        $sql->addSelect('category_id');
        $sql->addSelect('category_name');
        $sql->addSelect('category_code');
        $sql->addWhereOpr('category_indexing', 'on');
        ACMS_Filter::categoryTree($sql, $cid, 'ancestor-or-self');
        $sql->addOrder('category_left', 'DESC');
        $q  = $sql->get(dsn());
        DB::query($q, 'fetch');

        $_all = [];
        while ($row = DB::fetch($q)) {
            $_all[] = $row;
        }
        switch ($this->config['category_order']) {
            case 'child_order':
                break;
            case 'parent_order':
                $_all = array_reverse($_all);
                break;
            case 'current_order':
                $_all = [array_shift($_all)];
                break;
            default:
                break;
        }
        while ($_row = array_shift($_all)) {
            if (!empty($_all[0])) {
                $tpl->add(['glue', 'category:loop']);
            }
            $tpl->add('category:loop', [
                'name' => $_row['category_name'],
                'code' => $_row['category_code'],
                'url' => acmsLink([
                    'bid' => $bid,
                    'cid' => $_row['category_id'],
                ]),
            ]);
            $_all[] = DB::fetch($q);
        }
    }

    /**
     * サブカテゴリーを組み立て
     *
     * @param Template $tpl
     * @param int $eid
     * @param int|null $rvid
     * @return void
     */
    function buildSubCategory(Template $tpl, int $eid, ?int $rvid): void
    {
        $subCategories = loadSubCategoriesAll($eid, $rvid);
        foreach ($subCategories as $i => $category) {
            if ($i !== count($subCategories) - 1) {
                $tpl->add(['glue', 'sub_category:loop']);
            }
            $tpl->add('sub_category:loop', [
                'name' => $category['category_name'],
                'code' => $category['category_code'],
                'url' => acmsLink([
                    'cid' => $category['category_id'],
                ]),
            ]);
        }
    }

    /**
     * コメント件数を組み立て
     *
     * @param int $eid
     * @return array
     */
    function buildCommentAmount(int $eid): array
    {
        $sql = SQL::newSelect('comment');
        $sql->addSelect('*', 'comment_amount', null, 'COUNT');
        $sql->addWhereOpr('comment_entry_id', $eid);
        if (
            1
            && !sessionWithCompilation()
            && SUID !== ACMS_RAM::entryUser($eid)
        ) {
            $sql->addWhereOpr('comment_status', 'close', '<>');
        }
        return [
            'commentAmount' => intval(DB::query($sql->get(dsn()), 'one')),
            'commentUrl' => acmsLink([
                'eid' => $eid,
            ]),
        ];
    }

    /**
     * 位置情報を組み立て
     *
     * @param int $eid
     * @return array
     */
    protected function buildGeolocation(int $eid): array
    {
        $sql = SQL::newSelect('geo');
        $sql->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        $sql->addSelect('geo_geometry', 'longitude', null, POINT_X);
        $sql->addSelect('geo_zoom');
        $sql->addWhereOpr('geo_eid', $eid);

        if ($row = DB::query($sql->get(dsn()), 'row')) {
            return [
                'geo_lat' => $row['latitude'],
                'geo_lng' => $row['longitude'],
                'geo_zoom' => $row['geo_zoom'],
            ];
        }
        return [];
    }

    /**
     * タグを組み立て
     *
     * @param Template $tpl
     * @param int $eid
     * @return void
     */
    protected function buildEntryTag(Template $tpl, int $eid): void
    {
        if (RVID) {
            $sql = SQL::newSelect('tag_rev');
        } else {
            $sql = SQL::newSelect('tag');
        }
        $sql->addSelect('tag_name');
        $sql->addSelect('tag_blog_id');
        $sql->addWhereOpr('tag_entry_id', $eid);
        if (RVID) {
            $sql->addWhereOpr('tag_rev_id', RVID);
        }
        $sql->addOrder('tag_sort');
        $q = $sql->get(dsn());

        do {
            if (!DB::query($q, 'fetch')) {
                break;
            }
            if (!$row = DB::fetch($q)) {
                break;
            }
            $stack = [];
            $stack[] = $row;
            $stack[] = DB::fetch($q);
            while ($row = array_shift($stack)) {
                if (!empty($stack[0])) {
                    $tpl->add(['glue', 'tag:loop']);
                }
                $tpl->add('tag:loop', [
                    'name' => $row['tag_name'],
                    'url' => acmsLink([
                        'bid' => $row['tag_blog_id'],
                        'tag' => $row['tag_name'],
                    ]),
                ]);
                $stack[] = DB::fetch($q);
            }
        } while (false);
    }


    /**
     * 編集画面を組み立て
     *
     * @param int $bid
     * @param int $uid
     * @param int $cid
     * @param int $eid
     * @param Template $tpl
     * @param null|string $block
     * @return void
     */
    protected function buildAdminEntryEdit(int $bid, int $uid, ?int $cid, int $eid, Template $tpl, ?string $block = null): void
    {
        if (!SUID) {
            return;
        }
        $block = empty($block) ? [] : (is_array($block) ? $block : [$block]);
        if (ADMIN) {
            if ('entry-add' === substr(ADMIN, 0, 9)) {
                $tpl->add(array_merge(['adminEntryEdit'], $block));
            }
        } elseif (
            0
            || (!roleAvailableUser() && ((config('approval_contributor_edit_auth') !== 'on' && enableApproval()) || sessionWithCompilation() || (sessionWithContribution() && $uid == SUID)))
            || (roleAvailableUser() && (roleAuthorization('entry_edit_all', BID) || (roleAuthorization('entry_edit', BID) && $uid == SUID)))
        ) {
            $entry = ACMS_RAM::entry($eid);
            $val = [
                'bid' => $bid,
                'cid' => $cid,
                'eid' => $eid,
                'status.approval' => $entry['entry_approval'],
                'status.title' => ACMS_RAM::entryTitle($eid),
                'status.category' => ACMS_RAM::categoryName($cid),
                'status.url' => acmsLink([
                    'bid' => $bid,
                    'cid' => $cid,
                    'eid' => $eid,
                ]),
            ];

            if (!(sessionWithApprovalAdministrator() && $entry['entry_approval'] === 'pre_approval')) {
                $tpl->add(array_merge(['edit'], $block), $val);
                $tpl->add(array_merge(['revision'], $block), $val);
                if (BID === $bid) {
                    $types = configArray('column_add_type');
                    if (is_array($types)) {
                        $cnt = count($types);
                        $labels = configArray('column_add_type_label');
                        for ($i = 0; $i < $cnt; $i++) {
                            if (!$type = $types[$i]) {
                                continue;
                            }
                            if (!$label = $labels[$i]) {
                                continue;
                            }
                            $tpl->add(array_merge(['add:loop'], $block), $val + [
                                'label' => $label,
                                'type' => $type,
                                'className' => config('column_add_type_class', '', $i),
                                'icon' => config('column_add_type_icon', '', $i)
                            ]);
                        }
                    }
                    $statusBlock = ('open' === ACMS_RAM::entryStatus($eid)) ? 'close' : 'open';
                    $tpl->add(array_merge([$statusBlock], $block), $val);
                }
                if (!editionWithProfessional() || sessionWithApprovalAdministrator() || $entry['entry_approval'] === 'pre_approval') {
                    $tpl->add(array_merge(['delete'], $block), $val);
                }
                if (
                    1
                    && 'on' === config('entry_edit_inplace_enable')
                    && 'on' === config('entry_edit_inplace')
                    && (!enableApproval() || sessionWithApprovalAdministrator())
                    && VIEW === 'entry'
                ) {
                    $tpl->add(array_merge(['adminDetailEdit'], $block), $val);
                }
            } elseif (sessionWithApprovalAdministrator()) {
                $tpl->add(array_merge(['delete'], $block), $val);
            } else {
                $tpl->add(array_merge(['revision'], $block), $val);
            }
        }
    }

    /**
     * Bodyを組み立て
     *
     * @param Template $tpl
     * @param array $vars
     * @param array $row
     * @param int $serial
     * @return void
     */
    protected function buildBodyField(Template $tpl, array &$vars, array $row, int $serial = 0): void
    {
        $bid = intval($row['entry_blog_id']);
        $uid = $row['entry_user_id'] ? intval($row['entry_user_id']) : null;
        $cid = $row['entry_category_id'] ? intval($row['entry_category_id']) : null;
        $eid = intval($row['entry_id']);
        $inheritUrl = acmsLink([
            'bid' => $bid,
            'eid' => $eid,
        ]);
        $permalink = acmsLink([
            'bid' => $bid,
            'cid' => $cid,
            'eid' => $eid,
            'sid' => null,
        ], false);

        $RVID_ = RVID;
        if (!RVID && $row['entry_approval'] === 'pre_approval') {
            $RVID_ = 1;
        }
        if ($serial != 0) {
            if ($serial % 2 == 0) {
                $oddOrEven = 'even';
            } else {
                $oddOrEven = 'odd';
            }
            $vars['iNum'] = $serial;
            $vars['sNum'] = (($this->page - 1) * idval($this->config['limit'])) + $serial;
            $vars['oddOrEven'] = $oddOrEven;
        }
        // build tag
        if ($this->config['tag_on'] === 'on') {
            $this->buildEntryTag($tpl, $eid);
        }
        // build category loop
        if (!empty($cid) && $this->config['category_info_on'] === 'on') {
            $this->buildCategory($tpl, $cid, $bid);
        }
        // build sub category loop
        if ($this->config['category_info_on'] === 'on') {
            $this->buildSubCategory($tpl, $eid, $RVID_);
        }
        // build comment/trackbak/geolocation
        if ('on' == config('comment') && $this->config['comment_on'] === 'on') {
            $vars += $this->buildCommentAmount($eid);
        }
        if ($this->config['geolocation_on'] === 'on') {
            $vars += $this->buildGeolocation($eid);
        }
        // build summary
        if ($this->config['summary_on'] === 'on') {
            $vars = TplHelper::buildSummaryFulltext($vars, $eid, $this->summaryFulltextEagerLoadingData);
            if (
                1
                && isset($vars['summary'])
                && intval(config('entry_body_fulltext_width')) > 0
            ) {
                $width = intval(config('entry_body_fulltext_width'));
                $marker = config('entry_body_fulltext_marker');
                $vars['summary'] = mb_strimwidth($vars['summary'], 0, $width, $marker, 'UTF-8');
            }
        }
        // build primary image
        $clid = intval($row['entry_primary_image']);
        if (config('entry_body_image_on') === 'on') {
            $config = [
                'imageX' => config('entry_body_image_x', 200),
                'imageY' => config('entry_body_image_y', 200),
                'imageTrim' => config('entry_body_image_trim', 'off'),
                'imageCenter' => config('entry_body_image_zoom', 'off'),
                'imageZoom' => config('entry_body_image_center', 'off'),
            ];
            $tpl->add('mainImage', TplHelper::buildImage($tpl, $clid, $config, $this->mainImageEagerLoadingData));
        }
        // build related entry
        if ($this->config['related_entry_on'] === 'on') {
            TplHelper::buildRelatedEntriesList($tpl, $eid, $this->relatedEntryEagerLoadingData, ['relatedEntry', 'entry:loop']);
        } else {
            $tpl->add(['relatedEntry', 'entry:loop']);
        }
        // admin
        $this->buildAdminEntryEdit($bid, $uid, $cid, $eid, $tpl, 'entry:loop');
        // build entry field
        if ($this->config['entry_field_on'] === 'on') {
            $vars += $this->buildField(loadEntryField($eid, $RVID_, true), $tpl, 'entry:loop', 'entry');
        }
        // build user field
        if ($this->config['user_info_on'] === 'on') {
            $Field = ($this->config['user_field_on'] === 'on') ? loadUserField($uid) : new Field();
            $Field->setField('fieldUserName', ACMS_RAM::userName($uid));
            $Field->setField('fieldUserCode', ACMS_RAM::userCode($uid));
            $Field->setField('fieldUserStatus', ACMS_RAM::userStatus($uid));
            $Field->setField('fieldUserMail', ACMS_RAM::userMail($uid));
            $Field->setField('fieldUserMailMobile', ACMS_RAM::userMailMobile($uid));
            $Field->setField('fieldUserUrl', ACMS_RAM::userUrl($uid));
            $Field->setField('fieldUserIcon', loadUserIcon($uid));
            if ($large = loadUserLargeIcon($uid)) {
                $Field->setField('fieldUserLargeIcon', $large);
            }
            if ($orig = loadUserOriginalIcon($uid)) {
                $Field->setField('fieldUserOrigIcon', $orig);
            }
            $tpl->add('userField', $this->buildField($Field, $tpl));
        }
        // build category field
        if ($this->config['category_info_on'] === 'on') {
            $Field = ($this->config['category_field_on'] === 'on') ? loadCategoryField($cid) : new Field();
            $Field->setField('fieldCategoryName', ACMS_RAM::categoryName($cid));
            $Field->setField('fieldCategoryCode', ACMS_RAM::categoryCode($cid));
            $Field->setField('fieldCategoryUrl', acmsLink([
                'bid' => $bid,
                'cid' => $cid,
            ]));
            $Field->setField('fieldCategoryId', $cid);
            $tpl->add('categoryField', $this->buildField($Field, $tpl));
        }
        // build blog field
        if ($this->config['blog_info_on'] === 'on') {
            $Field = ($this->config['blog_field_on'] === 'on') ? loadBlogField($bid) : new Field();
            $Field->setField('fieldBlogName', ACMS_RAM::blogName($bid));
            $Field->setField('fieldBlogCode', ACMS_RAM::blogCode($bid));
            $Field->setField('fieldBlogUrl', acmsLink(['bid' => $bid]));
            $tpl->add('blogField', $this->buildField($Field, $tpl));
        }
        $link = (config('entry_body_link_url') === 'on') ? $row['entry_link'] : '';
        $vars += [
            'status' => $row['entry_status'],
            'titleUrl' => !empty($link) ? $link : $permalink,
            'title' => addPrefixEntryTitle(
                $row['entry_title'],
                $row['entry_status'],
                $row['entry_start_datetime'],
                $row['entry_end_datetime'],
                $row['entry_approval']
            ),
            'inheritUrl' => $inheritUrl,
            'permalink' => $permalink,
            'posterName' => ACMS_RAM::userName($uid),
            'entry:loop.bid' => $bid,
            'entry:loop.uid' => $uid,
            'entry:loop.cid' => $cid,
            'entry:loop.eid' => $eid,
            'entry:loop.bcd' => ACMS_RAM::blogCode($bid),
            'entry:loop.ucd' => ACMS_RAM::userCode($uid),
            'entry:loop.ccd' => ACMS_RAM::categoryCode($cid),
            'entry:loop.ecd' => ACMS_RAM::entryCode($eid),
            'entry:loop.class' => $this->config['loop_class'],
            'sort' => $row['entry_sort'],
            'usort' => $row['entry_user_sort'],
            'csort' => $row['entry_category_sort']
        ];
        if (!empty($link)) {
            $vars += [
                'link' => $link,
            ];
        }
        // build date
        if ($this->config['date_on'] === 'on') {
            $vars += $this->buildDate($row['entry_datetime'], $tpl, 'entry:loop');
        }
        if ($this->config['detail_date_on'] === 'on') {
            $vars += $this->buildDate($row['entry_updated_datetime'], $tpl, 'entry:loop', 'udate#');
            $vars += $this->buildDate($row['entry_posted_datetime'], $tpl, 'entry:loop', 'pdate#');
            $vars += $this->buildDate($row['entry_start_datetime'], $tpl, 'entry:loop', 'sdate#');
            $vars += $this->buildDate($row['entry_end_datetime'], $tpl, 'entry:loop', 'edate#');
        }
        // build new
        if (strtotime($row['entry_datetime']) + intval($this->config['newtime']) > requestTime()) {
            $tpl->add(['new:touch', 'entry:loop']); // 後方互換
            $tpl->add(['new', 'entry:loop']);
        }
    }

    /**
     * Not Found
     *
     * @param Template $tpl
     * @return string
     */
    protected function resultsNotFound(Template $tpl): string
    {
        $tpl->add('notFound');
        if ($this->config['notfoundStatus404'] === 'on') {
            httpStatusCode('404 Not Found');
        }
        return $tpl->get();
    }
}
