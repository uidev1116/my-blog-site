<?php

class ACMS_GET_Category_EntrySummary extends ACMS_GET_Category_EntryList
{
    var $_axis = array(
        'bid'   => 'self',
        'cid'   => 'self',
    );

    var $_endGluePoint = null;

    protected function initVars()
    {
        $config = array(
            'categoryOrder'             => config('category_entry_summary_category_order'),
            'categoryEntryListLevel'    => config('category_entry_summary_level'),
            'categoryIndexing'          => config('category_entry_summary_category_indexing'),
            'entryAmountZero'           => config('category_entry_summary_entry_amount_zero'),
            'subCategory'               => config('category_entry_summary_sub_category'),
            'order'                     => config('category_entry_summary_order'),
            'limit'                     => intval(config('category_entry_summary_limit')),
            'offset'                    => intval(config('category_entry_summary_offset')),
            'indexing'                  => config('category_entry_summary_indexing'),
            'secret'                    => config('category_entry_summary_secret'),
            'notfound'                  => config('mo_category_entry_summary_notfound'),
            'noimage'                   => config('category_entry_summary_noimage'),
            'unit'                      => config('category_entry_summary_unit'),
            'newtime'                   => config('category_entry_summary_newtime'),
            'imageX'                    => intval(config('category_entry_summary_image_x')),
            'imageY'                    => intval(config('category_entry_summary_image_y')),
            'imageTrim'                 => config('category_entry_summary_image_trim'),
            'imageZoom'                 => config('category_entry_summary_image_zoom'),
            'imageCenter'               => config('category_entry_summary_image_center'),
            'mainImageOn'               => config('category_entry_summary_image_on'),
            'categoryLoopClass'         => config('category_entry_summary_category_loop_class'),
            'fulltextWidth'             => config('category_entry_summary_fulltext_width'),
            'fulltextMarker'            => config('category_entry_summary_fulltext_marker'),
            'loop_class'                => config('category_entry_summary_entry_loop_class'),

            'entryFieldOn'              => config('category_entry_summary_entry_field_on'),
            'categoryInfoOn'            => 'on',
            'categoryFieldOn'           => config('category_entry_summary_category_field_on'),
            'userInfoOn'                => 'on',
            'userFieldOn'               => config('category_entry_summary_user_field_on'),
            'blogInfoOn'                => 'on',
            'blogFieldOn'               => config('category_entry_summary_blog_field_on'),
        );
        if(!empty($this->order)){$config['order'] = $this->order;}

        return $config;
    }

    protected function buildQuery($cid, &$Tpl)
    {
        $subCategory = isset($this->_config['subCategory']) && $this->_config['subCategory'] === 'on';

        $SQL1 = SQL::newSelect('entry', 'union1');
        $SQL1->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        $SQL1->addLeftJoin('category', 'entry_category_id', 'category_id');
        if ($subCategory) {
            $SQL1->addLeftJoin('entry_sub_category', 'entry_id', 'entry_sub_category_eid');
        }
        $SQL1->addWhereOpr('entry_category_id', $cid);
        $this->unionQuery($SQL1);
        $q1 = $SQL1->get(dsn());

        if ($subCategory) {
            $SQL2 = SQL::newSelect('entry', 'union2');
            $SQL2->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
            $SQL2->addLeftJoin('entry_sub_category', 'entry_id', 'entry_sub_category_eid');
            $SQL2->addLeftJoin('category', 'entry_sub_category_id', 'category_id');
            $SQL2->addWhereOpr('entry_sub_category_id', $cid);
            $this->unionQuery($SQL2);
            $q2 = $SQL2->get(dsn());

            $sql = '((' . $q1 . ') UNION (' . $q2 . '))';
        } else {
            $sql = '(' . $q1 . ')';
        }
        $SQL = SQL::newSelect($sql, 'master');

        $limit  = $this->_config['limit'];
        $offset = intval($this->_config['offset']);
        if ( 1 > $limit ) return '';

        $sortFd = ACMS_Filter::entryOrder($SQL, $this->_config['order'], $this->uid, $cid);
        $SQL->setLimit($limit, $offset);

        if ( !empty($sortFd) ) {
            $SQL->setGroup($sortFd);
        }
        $SQL->addGroup('entry_id');

        $q = $SQL->get(dsn(array('prefix' => '')));
        $all = DB::query($q, 'all');
        if (empty($all)) {
            return false;
        }
        $this->_endGluePoint = count($all);

        return $q;
    }

    protected function preBuildUnit()
    {
        $entryIds = array();
        $blogIds = array();
        $userIds = array();
        $categoryIds = array();

        foreach ($this->entries as $entry) {
            if (!empty($entry['entry_id'])) $entryIds[] = $entry['entry_id'];
            if (!empty($entry['entry_blog_id'])) $blogIds[] = $entry['entry_blog_id'];
            if (!empty($entry['entry_user_id'])) $userIds[] = $entry['entry_user_id'];
            if (!empty($entry['entry_category_id'])) $categoryIds[] = $entry['entry_category_id'];
        }

        // メイン画像のEagerLoading
        if (!isset($this->_config['mainImageOn']) || $this->_config['mainImageOn'] === 'on') {
            $this->eagerLoadingData['mainImage'] = Tpl::eagerLoadMainImage($this->entries);
        }
        // フルテキストのEagerLoading
        if (!isset($this->_config['fullTextOn']) || $this->_config['fullTextOn'] === 'on') {
            $this->eagerLoadingData['fullText'] = Tpl::eagerLoadFullText($entryIds);
        }
        // タグのEagerLoading
        $this->eagerLoadingData['tag'] =  Tpl::eagerLoadTag($entryIds);
        // エントリーフィールドのEagerLoading
        if (!isset($this->_config['entryFieldOn']) || $this->_config['entryFieldOn'] === 'on') {
            $this->eagerLoadingData['entryField'] = eagerLoadField($entryIds, 'eid');
        }
        // ユーザーフィールドのEagerLoading
        if (isset($this->_config['userInfoOn']) && $this->_config['userInfoOn'] === 'on') {
            $this->eagerLoadingData['userField'] = eagerLoadField($userIds, 'uid');
        }
        // ブログフィールドのEagerLoading
        if (isset($this->_config['blogInfoOn']) && $this->_config['blogInfoOn'] === 'on') {
            $this->eagerLoadingData['blogField'] = eagerLoadField($blogIds, 'bid');
        }
        // カテゴリーフィールドのEagerLoading
        if (isset($this->_config['categoryInfoOn']) && $this->_config['categoryInfoOn'] === 'on') {
            $this->eagerLoadingData['categoryField'] = eagerLoadField($categoryIds, 'cid');
        }
        // サブカテゴリーのEagerLoading
        if (isset($this->_config['categoryInfoOn']) && $this->_config['categoryInfoOn'] === 'on') {
            $this->eagerLoadingData['subCategory'] = eagerLoadSubCategories($entryIds);
        }
    }

    protected function buildUnit($eRow, &$Tpl, $cid, $level, $count = 0)
    {
        $this->buildSummary($Tpl, $eRow, $count, $this->_endGluePoint, $this->_config, array(), $this->eagerLoadingData);
    }

    protected function unionQuery($SQL)
    {
        $BlogSub = null;
        $CategorySub = null;

        if (!empty($this->bid)) {
            if ($this->blogAxis() === 'self') {
                $SQL->addWhereOpr('entry_blog_id', $this->bid);
                if ('on' === $this->_config['secret']) {
                    ACMS_Filter::blogDisclosureSecretStatus($SQL);
                } else {
                    ACMS_Filter::blogStatus($SQL);
                }
            } else {
                $BlogSub = SQL::newSelect('blog');
                $BlogSub->setSelect('blog_id');
                ACMS_Filter::blogTree($BlogSub, $this->bid, $this->blogAxis());
                if ('on' === $this->_config['secret']) {
                    ACMS_Filter::blogDisclosureSecretStatus($BlogSub);
                } else {
                    ACMS_Filter::blogStatus($BlogSub);
                }
            }
        }
        if ( $uid = intval($this->uid) ) {
            $SQL->addWhereOpr('entry_user_id', $this->uid);
        }
        if ( !empty($this->eid) ) {
            $SQL->addWhereOpr('entry_id', $this->eid);
        }
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);
        ACMS_Filter::entrySession($SQL);

        if ( !empty($this->tags) ) {
            ACMS_Filter::entryTag($SQL, $this->tags);
        }
        if ( !empty($this->keyword) ) {
            ACMS_Filter::entryKeyword($SQL, $this->keyword);
        }
        if ( !empty($this->Field) ) {
            if ( config('category_entry_summary_field_search') == 'entry' ) {
                ACMS_Filter::entryField($SQL, $this->Field);
            } else {
                ACMS_Filter::categoryField($SQL, $this->Field);
            }
        }
        if ( 'on' == $this->_config['indexing'] ) {
            $SQL->addWhereOpr('entry_indexing', 'on');
        }
        if ( 'on' <> $this->_config['noimage'] ) {
            $SQL->addWhereOpr('entry_primary_image', null, '<>');
        }

        //-------------------------
        // filter (blog, category)
        if ( $BlogSub ) {
            $SQL->addWhereIn('entry_blog_id', DB::subQuery($BlogSub));
        }

        return $SQL;
    }
}
