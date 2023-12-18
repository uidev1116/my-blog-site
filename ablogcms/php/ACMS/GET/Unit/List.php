<?php

class ACMS_GET_Unit_List extends ACMS_GET_Entry_Summary
{
    var $_axis = array(
        'bid' => 'descendant-or-self',
        'cid' => 'descendant-or-self',
    );

    var $_scope = array(
        'cid' => 'global',
        'eid' => 'global',
        'start' => 'global',
        'end' => 'global',
    );

    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $DB = DB::singleton(dsn());
        $vars = array();

        $SQL = SQL::newSelect('column');
        $SQL->addLeftJoin('entry', 'entry_id', 'column_entry_id');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');

        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);

        $this->userFilterQuery($SQL);
        $this->keywordFilterQuery($SQL);
        $this->tagFilterQuery($SQL);
        $this->fieldFilterQuery($SQL);

        if (!empty($this->eid)) {
            $SQL->addWhereOpr('column_entry_id', $this->eid);
        }
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);
        $SQL->addWhereIn('column_type', array_merge(
            configArray('column_list_type'),
            configArray('column_list_extends_type')
        ));

        $Amount = new SQL_Select($SQL);
        $Amount->setSelect('*', 'unit_amount', null, 'count');
        $itemsAmount = intval($DB->query($Amount->get(dsn()), 'one'));

        $order = config('column_list_order');
        if ('random' == $order) {
            $SQL->setOrder('RAND()');
        } else {
            if ('datetime-asc' == $order) {
                $SQL->addOrder('entry_datetime', 'ASC');
            } else {
                $SQL->addOrder('entry_datetime', 'DESC');
            }
        }

        $limit = intval(config('column_list_limit'));
        $from = ($this->page - 1) * $limit;
        $over = $itemsAmount <= $from;

        if (!$itemsAmount || $over) {
            return false;
        }

        $SQL->setLimit($limit, $from);

        $q = $SQL->get(dsn());

        // eager loading
        $mediaEagerLoading = array();
        $mediaIds = array();
        if ($DB->query($q, 'fetch') and $row = $DB->fetch($q)) {
            do {
                $type = detectUnitTypeSpecifier($row['column_type']);
                if ('media' === $type) {
                    $mediaIds[] = $row['column_field_1'];
                }
            } while ($row = $DB->fetch($q));
        }
        if ($mediaIds) {
            $SQL = SQL::newSelect('media');
            $SQL->addWhereIn('media_id', $mediaIds);
            $q2 = $SQL->get(dsn());
            $DB->query($q2, 'fetch');
            while ($media = $DB->fetch($q2)) {
                $mediaEagerLoading[$media['media_id']] = $media;
            }
        }
        if ($DB->query($q, 'fetch') and $row = $DB->fetch($q)) {
            do {
                $eid = intval($row['entry_id']);
                $cid = intval($row['category_id']);
                $bid = intval($row['blog_id']);
                $uid = intval($row['entry_user_id']);
                $type = detectUnitTypeSpecifier($row['column_type']);

                if ('media' === $type) {
                    $mediaId = $row['column_field_1'];
                    if (isset($mediaEagerLoading[$mediaId])) {
                        $media = $mediaEagerLoading[$mediaId];
                        $mediaType = $media['media_type'];
                        if ($mediaType === 'image') {
                            $row['normal'] = Media::urlencode($media['media_path']);
                            $row['large'] = Media::urlencode($media['media_original']);
                        } else if ($mediaType === 'file') {
                            if (empty($media['media_status'])) {
                                $row['download'] = '/' . Media::getFileOldPermalink(Media::urlencode($media['media_path']), false);
                            } else {
                                $row['download'] = '/' . Media::getFilePermalink($media['media_id'], false);
                            }
                        }
                    }
                }

                if ('image' === $type) {
                    $normal = $row['column_field_2'];
                    $tiny = preg_replace('@(^|/)(?=[^/]+$)@', '$1tiny-', $normal);
                    $large = preg_replace('@(^|/)(?=[^/]+$)@', '$1large-', $normal);
                    $square = preg_replace('@(^|/)(?=[^/]+$)@', '$1square-', $normal);

                    $row['tiny'] = $tiny;
                    $row['normal'] = $normal;
                    if (Storage::isFile(ARCHIVES_DIR . $large)) {
                        $row['large'] = $large;
                    }
                    if (Storage::isFile(ARCHIVES_DIR . $square)) {
                        $row['square'] = $square;
                    }
                }

                if ('custom' === $type) {
                    $field = acmsUnserialize($row['column_field_6']);
                    $block = 'unit#' . $row['column_type'];
                    $Tpl->add(array($block, 'unit:loop'), $this->buildField($field, $Tpl, array($block, 'unit:loop')));
                }

                $row['entry_url'] = acmsLink(array(
                    'bid' => $bid,
                    'eid' => $eid,
                ));
                if (!empty($cid)) {
                    $row['category_url'] = acmsLink(array(
                        'bid' => $bid,
                        'cid' => $cid,
                    ));
                } else {
                    unset($row['category_name']);
                }
                $row['blog_url'] = acmsLink(array(
                    'bid' => $bid,
                ));

                $tmp = array();
                foreach ($row as $key => $val) {
                    if (empty($val)) {
                        unset($row[$key]);
                    }
                    $tmp[preg_replace('/column/', 'unit', $key)] = $val;
                }
                $row = $tmp;

                $row['unit:loop.class'] = config('column_list_loop_class');

                //-------------
                // entry field
                if (config('column_list_entry_on') === 'on') {
                    if (config('column_list_entry_field') === 'on') {
                        $Field = loadEntryField($eid);
                    } else {
                        $Field = new Field();
                    }
                    $Field->setField('fieldEntryTitle', ACMS_RAM::entryTitle($eid));
                    $Field->setField('fieldEntryCode', ACMS_RAM::entryCode($eid));
                    $Field->setField('fieldEntryDatetime', ACMS_RAM::entryDatetime($eid));

                    $Tpl->add(array('entryField', 'unit:loop'), $this->buildField($Field, $Tpl, 'unit:loop'));
                }

                //-------------
                // user field
                if (config('column_list_user_on') === 'on') {
                    if (config('column_list_user_field_on') === 'on') {
                        $Field = loadUserField($uid);
                    } else {
                        $Field = new Field();
                    }
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
                    $Tpl->add(array('userField', 'unit:loop'), $this->buildField($Field, $Tpl, 'unit:loop'));
                }

                //------------
                // blog field
                if (config('column_list_blog_on') === 'on') {
                    if (config('column_list_blog_field_on') === 'on') {
                        $Field = loadBlogField($bid);
                    } else {
                        $Field = new Field();
                    }
                    $Field->setField('fieldBlogName', ACMS_RAM::blogName($bid));
                    $Field->setField('fieldBlogCode', ACMS_RAM::blogCode($bid));
                    $Field->setField('fieldBlogUrl', acmsLink(array('bid' => $bid, '_protocol' => 'http'), false));

                    $Tpl->add(array('blogField', 'unit:loop'), $this->buildField($Field, $Tpl, 'unit:loop'));
                }

                //----------------
                // category field
                if (!empty($cid) && config('column_list_category_on') === 'on') {
                    if (config('column_list_category_field_on') === 'on') {
                        $Field = loadCategoryField($cid);
                    } else {
                        $Field = new Field();
                    }
                    $Field->setField('fieldCategoryName', ACMS_RAM::categoryName($cid));
                    $Field->setField('fieldCategoryCode', ACMS_RAM::categoryCode($cid));
                    $Field->setField('fieldCategoryUrl', acmsLink(array('cid' => $cid, '_protocol' => 'http'), false));
                    $Field->setField('fieldCategoryId', $cid);

                    $Tpl->add(array('categoryField', 'unit:loop'), $this->buildField($Field, $Tpl, 'unit:loop'));
                }

                $Tpl->add('column:loop', $row);
                $Tpl->add('unit:loop', $row);

            } while ($row = $DB->fetch($q));
        }

        //-------
        // pager
        if ('random' <> $order && config('column_list_pager_on') === 'on') {
            $vars += $this->buildPager($this->page, $limit, $itemsAmount, config('column_list_pager_delta'),
                config('column_list_pager_cur_attr'), $Tpl);
        }

        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
