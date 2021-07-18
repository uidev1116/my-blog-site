<?php

class ACMS_GET_Category_EntryList extends ACMS_GET
{
    var $_scope  = array(
        'cid'   => 'global',
    );

    var $_config = array();

    protected $entries = array();

    protected $eagerLoadingData = array();

    protected function initVars()
    {
        return array(
            'categoryOrder'                 => config('category_entry_list_category_order'),
            'categoryEntryListLevel'        => config('category_entry_list_level'),
            'categoryIndexing'              => config('category_entry_list_category_indexing'),
            'entryAmountZero'               => config('category_entry_list_entry_amount_zero'),
            'subCategory'                   => config('category_entry_list_sub_category'),
            'entryActiveCategory'           => config('category_entry_list_entry_active_category'),
            'order'                         => config('category_entry_list_entry_order'),
            'limit'                         => config('category_entry_list_entry_limit'),
            'indexing'                      => config('category_entry_list_entry_indexing'),
            'categoryLoopClass'             => config('category_entry_list_category_loop_class'),
            'entryLoopClass'                => config('category_entry_list_entry_loop_class'),
        );
    }

    public function get()
    {
        $this->_config = $this->initVars();

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $DB     = DB::singleton(dsn());

        $aryStack   = array(intval($this->cid));
        $aryCount   = array();
        $aryHidden  = array();
        $eagerLoadCategoryFields = $this->eagerLoadCategoryFields();

        while ( array_key_exists(0, $aryStack) ) {
            $pid    = $aryStack[0];

            $SQL    = SQL::newSelect('category');
            $SQL->addLeftJoin('blog', 'blog_id', 'category_blog_id');
            $SQL->addWhereOpr('category_parent', $pid);
            ACMS_Filter::blogTree($SQL, $this->bid, 'ancestor-or-self');
            ACMS_Filter::categoryStatus($SQL);
            ACMS_Filter::categoryOrder($SQL, $this->_config['categoryOrder']);

            $Where  = SQL::newWhere();
            $Where->addWhereOpr('category_blog_id', $this->bid, '=', 'OR');
            $Where->addWhereOpr('category_scope', 'global', '=', 'OR');
            $SQL->addWhere($Where);

            $cQ = $SQL->get(dsn());

            if ( !$DB->isFetched($cQ) and !$DB->query($cQ, 'fetch') ) {
                array_shift($aryStack);
                continue;
            }

            $level  = 0;
            foreach ( $aryStack as $cid ) {
                if ( empty($aryHidden[$cid]) ) $level++;
            }
            $cid    = null;

            if ( intval($this->_config['categoryEntryListLevel']) >= $level ) { while ( !!($cRow = $DB->fetch($cQ)) ) {
                $cid    = intval($cRow['category_id']);
                $this->entries = array();

                //--------------------
                // entry build query
                if ( !('on' == $this->_config['categoryIndexing'] and 'on' <> $cRow['category_indexing']) ) {
                    if ( $eQ = $this->buildQuery($cid, $Tpl) ) {
                        if ( !!$DB->query($eQ, 'fetch') and !!($eRow = $DB->fetch($eQ)) );
                    }
                }

                if ( 1
                    and !('on' == $this->_config['categoryIndexing'] and 'on' <> $cRow['category_indexing'])
                    and !('on' <> $this->_config['entryAmountZero'] and empty($eRow))
                ) {
                    //-------
                    // entry
                    if ( isset($this->_config['notfound']) && $this->_config['notfound'] === 'on' && empty($eRow) ) {
                        $Tpl->add('notFound');
                    }
                    if( isset( $this->_config['entryActiveCategory'] ) && 'on' == $this->_config['entryActiveCategory'] && ( $cid != CID || intval(CID) == 0 ) ){
                    }else {
                        $i = 0;
                        if ( !empty($eRow) ) { do {
                            $i++;
                            $this->entries[$i] = $eRow;
                        } while ( !!($eRow = $DB->fetch($eQ) ) ); }
                        $this->preBuildUnit();
                        foreach ($this->entries as $i => $entry) {
                            $this->buildUnit($entry, $Tpl, $cid, $level, $i);
                        }
                    }

                    //----------
                    // category
                    $vars   = array();
                    $vars   += array(
                        'categoryUrl'   => acmsLink(array(
                            'bid'   => $this->bid,
                            'cid'   => $cid,
                        )),
                        'categoryName'  => $cRow['category_name'],
                        'categoryLevel' => $level,
                        'categoryCode'  => $cRow['category_code'],
                        'categoryId'    => $cid,
                        'categoryPid'   => $pid,
                        'category:loop.class'   => $this->_config['categoryLoopClass'],
                    );

                    if (!isset($this->_config['categoryFieldOn']) or $this->_config['categoryFieldOn'] === 'on') {
                        if (isset($eagerLoadCategoryFields[$cid])) {
                            $vars += $this->buildField($eagerLoadCategoryFields[$cid], $Tpl);
                        }
                    }

                    if ( empty($aryCount[$pid]) ) {
                        if ( empty($aryHidden[$pid]) ) {
                            $Tpl->add('categoryUl#front');
                        }
                        $aryCount[$pid] = 0;
                    }
                    $aryCount[$pid]++;

                    $Tpl->add('category:loop', $vars);
                    $Tpl->add('categoryEntryList:loop', array('debug' => 'bug'));
                } else {
                    $aryHidden[$cid]    = true;
                }

                if ( intval($this->_config['categoryEntryListLevel']) >= $level ) array_unshift($aryStack, $cid);
                break;
            } }

            if ( is_null($cid) ) {
                array_shift($aryStack);
                if ( empty($aryHidden[$pid]) ) {
                    if ( !empty($aryCount[$pid]) ) {
                        $Tpl->add('categoryUl#rear');
                        $Tpl->add('categoryEntryList:loop');
                    }
                    if ( !empty($aryStack) ) {
                        $Tpl->add('categoryLi#rear');
                        $Tpl->add('categoryEntryList:loop');
                    }
                }
            }
        }

        return $Tpl->get();
    }

    protected function unionQuery($SQL)
    {
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);
        ACMS_Filter::entrySession($SQL);
        if ('on' == $this->_config['indexing']) {
            $SQL->addWhereOpr('entry_indexing', 'on');
        }
        $SQL->addWhereOpr('entry_blog_id', $this->bid);

        if (!empty($this->tags)) {
            ACMS_Filter::entryTag($SQL, $this->tags);
        }
        if (!empty($this->keyword)) {
            ACMS_Filter::entryKeyword($SQL, $this->keyword);
        }
        if (!empty($this->Field)) {
            if (config('category_entry_list_field_search') == 'entry') {
                ACMS_Filter::entryField($SQL, $this->Field);
            } else {
                ACMS_Filter::categoryField($SQL, $this->Field);
            }
        }
    }

    protected function buildQuery($cid, &$Tpl)
    {
        $subCategory = isset($this->_config['subCategory']) && $this->_config['subCategory'] === 'on';

        $SQL1 = SQL::newSelect('entry', 'union1');
        $SQL1->addLeftJoin('category', 'entry_category_id', 'category_id');
        if ($subCategory) {
            $SQL1->addLeftJoin('entry_sub_category', 'entry_id', 'entry_sub_category_eid');
        }
        $SQL1->addWhereOpr('entry_category_id', $cid);
        $this->unionQuery($SQL1);
        $q1 = $SQL1->get(dsn());

        if ($subCategory) {
            $SQL2 = SQL::newSelect('entry', 'union2');
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

        $sortFd = ACMS_Filter::entryOrder($SQL, $this->_config['order'], $this->uid, $cid);
        if ( !empty($sortFd) ) {
            $SQL->setGroup($sortFd);
        }
        $SQL->addGroup('entry_id');

        $SQL->setLimit($this->_config['limit']);
        $eQ = $SQL->get(dsn(array('prefix' => '')));

        return $eQ;
    }

    protected function buildUnit($eRow, &$Tpl, $cid, $level, $count = 0)
    {
        $eid  = intval($eRow['entry_id']);
        if ( !empty($eRow['entry_link']) ) {
            $entryUrl   = $eRow['entry_link'];
        } else {
            $entryUrl   = acmsLink(array(
                'bid'   => $this->bid,
                'cid'   => $cid,
                'eid'   => $eid,
            ));
        }
        $vars   = array();
        $vars   += array(
            'entryUrl'      => $entryUrl,
            'entryTitle'    => addPrefixEntryTitle($eRow['entry_title']
                , $eRow['entry_status']
                , $eRow['entry_start_datetime']
                , $eRow['entry_end_datetime']
                , $eRow['entry_approval']
            ),
            'entryLevel'    => $level,
            'entryCode'     => $eRow['entry_code'],
            'entryId'       => $eid,
            'entry:loop.class'  => $this->_config['entryLoopClass'],
        );
        $vars   += $this->buildField(loadEntryField($eid), $Tpl);
        $Tpl->add('entry:loop', $vars);
    }

    protected function preBuildUnit()
    {

    }

    protected function eagerLoadCategoryFields()
    {
        $SQL = SQL::newSelect('category');
        $SQL->setSelect('category_id');
        ACMS_Filter::categoryTree($SQL, $this->cid, 'descendant');
        ACMS_Filter::categoryStatus($SQL);

        $Where  = SQL::newWhere();
        $Where->addWhereOpr('category_blog_id', $this->bid, '=', 'OR');
        $Where->addWhereOpr('category_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);

        $categoryIds = DB::query($SQL->get(dsn()), 'list');

        return eagerLoadField($categoryIds, 'cid');
    }
}
