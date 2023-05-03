<?php

class ACMS_GET_Entry_Body extends ACMS_GET_Entry
{
    var $_axis = array(
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    );

    var $_scope = array(
        'uid'       => 'global',
        'cid'       => 'global',
        'eid'       => 'global',
        'keyword'   => 'global',
        'tag'       => 'global',
        'field'     => 'global',
        'date'      => 'global',
        'start'     => 'global',
        'end'       => 'global',
        'page'      => 'global',
        'order'     => 'global',
    );

    /**
     * フルテキストのEagerLoadingData
     *
     * @var array
     */
    protected $summaryFulltextEagerLoadingData = array();

    /**
     * メインイメージのEagerLoadingData
     *
     * @var array
     */
    protected $mainImageEagerLoadingData = array();

    /**
     * 関連エントリーのEagerLoadingData
     *
     * @var array
     */
    protected $relatedEntryEagerLoadingData = array();

    function initConfig()
    {
        // entry
        $this->order = array(
            $this->order ? $this->order : config('entry_body_order'),
            config('entry_body_order2'),
        );
        $this->limit                = config('entry_body_limit');
        $this->offset               = config('entry_body_offset');
        $this->image_viewer         = config('entry_body_image_viewer');
        $this->indexing             = config('entry_body_indexing');
        $this->sub_category         = config('entry_body_sub_category');
        $this->newtime              = config('entry_body_newtime');
        $this->serial_navi_ignore_category_on = config('entry_body_serial_navi_ignore_category');
        $this->tag_on               = config('entry_body_tag_on');
        $this->summary_on           = config('entry_body_summary_on');
        $this->related_entry_on     = config('entry_body_related_entry_on', 'off');
        $this->show_all_index       = config('entry_body_show_all_index');
        $this->date_on              = config('entry_body_date_on');
        $this->detail_date_on       = config('entry_body_detail_date_on');
        $this->comment_on           = config('entry_body_comment_on');
        $this->geolocation_on       = config('entry_body_geolocation_on');
        $this->trackback_on         = config('entry_body_trackback_on');
        $this->serial_navi_on       = config('entry_body_serial_navi_on');
        $this->simple_pager_on      = config('entry_body_simple_pager_on');
        $this->category_order       = config('entry_body_category_order');
        $this->notfoundStatus404    = config('entry_body_notfound_status_404');
        // micropage
        $this->micropager_on        = config('entry_body_micropage');
        $this->micropager_delta     = config('entry_body_micropager_delta');
        $this->micropager_cur_attr  = config('entry_body_micropager_cur_attr');
        // pager
        $this->pager_on             = config('entry_body_pager_on');
        $this->pager_delta          = config('entry_body_pager_delta');
        $this->pager_cur_attr       = config('entry_body_pager_cur_attr');
        // field
        $this->entry_field_on       = config('entry_body_entry_field_on');
        $this->user_field_on        = config('entry_body_user_field_on');
        $this->category_field_on    = config('entry_body_category_field_on');
        $this->blog_field_on        = config('entry_body_blog_field_on');
        // base field
        $this->user_info_on         = config('entry_body_user_info_on');
        $this->category_info_on     = config('entry_body_category_info_on');
        $this->blog_info_on         = config('entry_body_blog_info_on');
        $this->loop_class           = config('entry_body_loop_class');
    }

    function buildSubCategory(& $Tpl, $eid, $rvid)
    {
        $subCategories = loadSubCategoriesAll($eid, $rvid);

        foreach ($subCategories as $i => $category) {
            if ($i !== count($subCategories) - 1) {
                $Tpl->add(array('glue', 'sub_category:loop'));
            }
            $Tpl->add('sub_category:loop', array(
                'name'  => $category['category_name'],
                'code'  => $category['category_code'],
                'url'   => acmsLink(array(
                    'cid'   => $category['category_id'],
                )),
            ));
        }
    }

    function buildCategory(& $Tpl, $cid, $bid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('category');
        $SQL->addSelect('category_id');
        $SQL->addSelect('category_name');
        $SQL->addSelect('category_code');
        $SQL->addWhereOpr('category_indexing', 'on');
        ACMS_Filter::categoryTree($SQL, $cid, 'ancestor-or-self');
        $SQL->addOrder('category_left', 'DESC');
        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        $_all    = array();

        while ( $row = $DB->fetch($q) ) {
            $_all[] = $row;
        }

        switch ( $this->category_order ) {
            case 'child_order' :
                break;
            case 'parent_order' :
                $_all = array_reverse($_all);
                break;
            case 'current_order' :
                $_all = array(array_shift($_all));
                break;
            default :
                break;
        }

        while ( $_row = array_shift($_all) ) {
            if ( !empty($_all[0]) ) {
                $Tpl->add(array('glue', 'category:loop'));
            }

            $Tpl->add('category:loop', array(
                'name'  => $_row['category_name'],
                'code'  => $_row['category_code'],
                'url'   => acmsLink(array(
                    'bid'   => $bid,
                    'cid'   => $_row['category_id'],
                )),
            ));
            $_all[] = $DB->fetch($q);
        }

        return true;
    }

    function buildCommentAmount($eid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('comment');
        $SQL->addSelect('*', 'comment_amount', null, 'COUNT');
        $SQL->addWhereOpr('comment_entry_id', intval($eid));

        if ( 1
            and !sessionWithCompilation()
            and SUID <> ACMS_RAM::entryUser($eid)
        ) {
            $SQL->addWhereOpr('comment_status', 'close', '<>');
        }

        return array(
            'commentAmount' => intval($DB->query($SQL->get(dsn()), 'one')),
            'commentUrl'    => acmsLink(array('eid' => intval($eid),)),
        );
    }

    function buildTrackbackAmount($eid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('trackback');
        $SQL->setSelect('*', 'trackback_amount', null, 'COUNT');
        $SQL->addWhereOpr('trackback_entry_id', intval($eid));

        if ( 1
            and !sessionWithCompilation()
            and SUID <> ACMS_RAM::entryUser($eid)
        ) {
            $SQL->addWhereOpr('trackback_status', 'close', '<>');
        }

        return array(
            'trackbackAmount'   => intval($DB->query($SQL->get(dsn()), 'one')),
            'trackbackUrl'      => acmsLink(array('eid' => intval($eid))),
        );
    }

    function buildGeolocation($eid)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('geo');
        $SQL->addSelect('geo_geometry', 'latitude', null, POINT_Y);
        $SQL->addSelect('geo_geometry', 'longitude', null, POINT_X);
        $SQL->addSelect('geo_zoom');
        $SQL->addWhereOpr('geo_eid', $eid);

        if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
            return array(
                'geo_lat' => $row['latitude'],
                'geo_lng' => $row['longitude'],
                'geo_zoom' => $row['geo_zoom'],
            );
        }
        return array();
    }

    function buildTag(& $Tpl, $eid)
    {
        $DB     = DB::singleton(dsn());
        if ( RVID ) {
            $SQL    = SQL::newSelect('tag_rev');
        } else {
            $SQL    = SQL::newSelect('tag');
        }
        $SQL->addSelect('tag_name');
        $SQL->addSelect('tag_blog_id');
        $SQL->addWhereOpr('tag_entry_id', $eid);
        if ( RVID ) {
            $SQL->addWhereOpr('tag_rev_id', RVID);
        }
        $SQL->addOrder('tag_sort');

        $q  = $SQL->get(dsn());

        do {
            if ( !$DB->query($q, 'fetch') ) break;
            if ( !$row = $DB->fetch($q) ) break;
            $stack  = array();
            $stack[] = $row;
            $stack[] = $DB->fetch($q);
            while ( $row = array_shift($stack) ) {
                if ( !empty($stack[0]) ) $Tpl->add(array('glue', 'tag:loop'));
                $Tpl->add('tag:loop', array(
                    'name'  => $row['tag_name'],
                    'url'   => acmsLink(array(
                        'bid'   => $row['tag_blog_id'],
                        'tag'   => $row['tag_name'],
                    )),
                ));
                $stack[] = $DB->fetch($q);
            }
        } while ( false );

        return true;
    }

    function buildAdminEntryEdit($bid, $uid, $cid, $eid, & $Tpl, $block=null)
    {
        if ( !SUID ) {
            return false;
        }

        $block  = empty($block) ? array() : (is_array($block) ? $block : array($block));
        if ( ADMIN ) {
            if ( 'entry-add' == substr(ADMIN, 0, 9) ) {
                $Tpl->add(array_merge(array('adminEntryEdit'), $block));
            }
        } else if ( 0
            || ( !roleAvailableUser() && ( (config('approval_contributor_edit_auth') !== 'on' && enableApproval()) || sessionWithCompilation() || (sessionWithContribution() && $uid == SUID) ) )
            || ( roleAvailableUser() && ( roleAuthorization('entry_edit_all', BID) || (roleAuthorization('entry_edit', BID) && $uid == SUID) ) )
        ) {
            $entry  = ACMS_RAM::entry($eid);

            $val    = array(
                'bid'   => $bid,
                'cid'   => $cid,
                'eid'   => $eid,
                'status.approval'   => $entry['entry_approval'],
                'status.title'      => ACMS_RAM::entryTitle($eid),
                'status.category'   => ACMS_RAM::categoryName($cid),
                'status.url'        => acmsLink(array('bid'=>$bid, 'cid'=>$cid, 'eid'=>$eid, 'sid'=>null, '_protocol'=>'http')),
            );

            if ( !(sessionWithApprovalAdministrator() && $entry['entry_approval'] === 'pre_approval') ) {
                if ( IS_LICENSED ) {
                    $Tpl->add(array_merge(array('edit'), $block), $val);
                    $Tpl->add(array_merge(array('revision'), $block), $val);
                    if ( BID == $bid ) {
                        $types  = configArray('column_add_type');
                        if ( is_array($types) ) {
                            $cnt    = count($types);
                            $labels = configArray('column_add_type_label');
                            for ( $i=0; $i<$cnt; $i++ ) {
                                if ( !$type = $types[$i] ) continue;
                                if ( !$label = $labels[$i] ) continue;
                                $Tpl->add(array_merge(array('add:loop'), $block), $val    + array(
                                    'label' => $label,
                                    'type'  => $type,
                                    'className' => config('column_add_type_class', '', $i),
                                    'icon' => config('column_add_type_icon', '', $i)
                                ));
                            }
                        }
                        $statusBlock    = ( 'open' == ACMS_RAM::entryStatus($eid) ) ? 'close' : 'open';
                        $Tpl->add(array_merge(array($statusBlock), $block), $val);
                    }
                }
                if ( !editionWithProfessional() || sessionWithApprovalAdministrator() || $entry['entry_approval'] === 'pre_approval') {
                    $Tpl->add(array_merge(array('delete'), $block), $val);
                }

                if ( 1
                    and 'on' == config('entry_edit_inplace_enable')
                    and 'on' == config('entry_edit_inplace')
                    and ( !enableApproval() || sessionWithApprovalAdministrator() )
                    and VIEW == 'entry'
                ) {
                    $Tpl->add(array_merge(array('adminDetailEdit'), $block), $val);
                }
            } else if ( sessionWithApprovalAdministrator() ) {
                $Tpl->add(array_merge(array('delete'), $block), $val);
            } else {
                $Tpl->add(array_merge(array('revision'), $block), $val);
            }
        }
        return true;
    }

    function buildBodyField(&$Tpl, &$vars, $row, $serial = 0)
    {
        $bid    = $row['entry_blog_id'];
        $uid    = $row['entry_user_id'];
        $cid    = $row['entry_category_id'];
        $eid    = $row['entry_id'];
        $inheritUrl = acmsLink(array(
            'bid'   => $bid,
            'eid'   => $eid,
        ));
        $permalink  = acmsLink(array(
            'bid'   => $bid,
            'cid'   => $cid,
            'eid'   => $eid,
            'sid'   => null,
        ), false);

        $RVID_ = RVID;
        if ( !RVID && $row['entry_approval'] === 'pre_approval' ) {
            $RVID_ = 1;
        }

        if ( $serial != 0 ) {
            if ( $serial % 2 == 0 ) {
                $oddOrEven  = 'even';
            } else {
                $oddOrEven  = 'odd';
            }

            $vars['iNum']       = $serial;
            $vars['sNum']       = (($this->page - 1) * $this->limit) + $serial;
            $vars['oddOrEven']  = $oddOrEven;
        }

        //-----------
        // build tag
        if ( $this->tag_on === 'on' ) {
            $this->buildTag($Tpl, $eid);
        }

        //---------------------
        // build category loop
        if ( !empty($cid) and $this->category_info_on === 'on' ) {
            $this->buildCategory($Tpl, $cid, $bid);
        }

        //------------------------
        // build sub category loop
        if ($this->category_info_on === 'on') {
            $this->buildSubCategory($Tpl, $eid, $RVID_);
        }

        //-----------------------------------
        // build comment/trackbak/geolocation
        if ( 'on' == config('comment') and $this->comment_on === 'on' ) {
            $vars += $this->buildCommentAmount($eid);
        }
        if ( 'on' == config('trackback') and $this->trackback_on === 'on' ) {
            $vars += $this->buildTrackbackAmount($eid);
        }
        if ( $this->geolocation_on === 'on') {
            $vars += $this->buildGeolocation($eid);
        }

        //---------------
        // build summary
        if ($this->summary_on === 'on') {
            $vars = Tpl::buildSummaryFulltext($vars, $eid, $this->summaryFulltextEagerLoadingData);
            if ( 1
                && isset($vars['summary'])
                && intval(config('entry_body_fulltext_width')) > 0
            ) {
                $width  = intval(config('entry_body_fulltext_width'));
                $marker = config('entry_body_fulltext_marker');
                $vars['summary']    = mb_strimwidth($vars['summary'], 0, $width, $marker, 'UTF-8');
            }
        }

        //---------------------
        // build primary image
        $clid = intval($row['entry_primary_image']);
        if (config('entry_body_image_on') === 'on') {
            $config = array(
                'imageX' => config('entry_body_image_x', 200),
                'imageY' => config('entry_body_image_y', 200),
                'imageTrim' => config('entry_body_image_trim', 'off'),
                'imageCenter' => config('entry_body_image_zoom', 'off'),
                'imageZoom' => config('entry_body_image_center', 'off'),
            );
            $Tpl->add('mainImage', Tpl::buildImage($Tpl, $clid, $config, $this->mainImageEagerLoadingData));
        }

        //---------------------
        // build related entry
        if ($this->related_entry_on === 'on') {
            Tpl::buildRelatedEntriesList($Tpl, $eid, $this->relatedEntryEagerLoadingData, array('relatedEntry', 'entry:loop'));
        } else {
            $Tpl->add(array('relatedEntry', 'entry:loop'));
        }

        //-------
        // admin
        $this->buildAdminEntryEdit($bid, $uid, $cid, $eid, $Tpl, 'entry:loop');

        //-------------------
        // build entry field
        if ( $this->entry_field_on === 'on' ) {
            $vars += $this->buildField(loadEntryField($eid, $RVID_, true), $Tpl, 'entry:loop', 'entry');
        }

        //-------------------
        // build user field
        if ( $this->user_info_on === 'on' ) {
            $Field = ($this->user_field_on === 'on') ? loadUserField($uid) : new Field();

            $Field->setField('fieldUserName', ACMS_RAM::userName($uid));
            $Field->setField('fieldUserCode', ACMS_RAM::userCode($uid));
            $Field->setField('fieldUserStatus', ACMS_RAM::userStatus($uid));
            $Field->setField('fieldUserMail', ACMS_RAM::userMail($uid));
            $Field->setField('fieldUserMailMobile', ACMS_RAM::userMailMobile($uid));
            $Field->setField('fieldUserUrl', ACMS_RAM::userUrl($uid));
            $Field->setField('fieldUserIcon', loadUserIcon($uid));
            if ( $large = loadUserLargeIcon($uid) ) {
                $Field->setField('fieldUserLargeIcon', $large);
            }
            $Tpl->add('userField', $this->buildField($Field, $Tpl));
        }

        //----------------------
        // build category field
        if ( $this->category_info_on === 'on' ) {
            $Field = ($this->category_field_on === 'on') ? loadCategoryField($cid) : new Field();
            $Field->setField('fieldCategoryName', ACMS_RAM::categoryName($cid));
            $Field->setField('fieldCategoryCode', ACMS_RAM::categoryCode($cid));
            $Field->setField('fieldCategoryUrl', acmsLink(array(
                'bid'   => $bid,
                'cid'   => $cid,
            )));
            $Field->setField('fieldCategoryId', $cid);
            $Tpl->add('categoryField', $this->buildField($Field, $Tpl));
        }

        //------------------
        // build blog field
        if ( $this->blog_info_on === 'on' ) {
            $Field = ($this->blog_field_on === 'on') ? loadBlogField($bid) : new Field();

            $Field->setField('fieldBlogName', ACMS_RAM::blogName($bid));
            $Field->setField('fieldBlogCode', ACMS_RAM::blogCode($bid));
            $Field->setField('fieldBlogUrl', acmsLink(array('bid' => $bid)));
            $Tpl->add('blogField', $this->buildField($Field, $Tpl));
        }
        $link   = ( config('entry_body_link_url') === 'on' ) ? $row['entry_link'] : '';
        $vars   += array(
            'status'    => $row['entry_status'],
            'titleUrl'  => !empty($link) ? $link : $permalink,
            'title'     => addPrefixEntryTitle($row['entry_title']
                , $row['entry_status']
                , $row['entry_start_datetime']
                , $row['entry_end_datetime']
                , $row['entry_approval']
            ),
            'inheritUrl'        => $inheritUrl,
            'permalink'         => $permalink,
            'posterName'        => ACMS_RAM::userName($uid),
            'entry:loop.bid'    => $bid,
            'entry:loop.uid'    => $uid,
            'entry:loop.cid'    => $cid,
            'entry:loop.eid'    => $eid,
            'entry:loop.bcd'    => ACMS_RAM::blogCode($bid),
            'entry:loop.ucd'    => ACMS_RAM::userCode($uid),
            'entry:loop.ccd'    => ACMS_RAM::categoryCode($cid),
            'entry:loop.ecd'    => ACMS_RAM::entryCode($eid),
            'entry:loop.class'  => $this->loop_class,
        );
        if ( !empty($link) ) {
            $vars   += array(
                'link'  => $link,
            );
        }

        //------------
        // build date
        if ( $this->date_on === 'on' ) {
            $vars   += $this->buildDate($row['entry_datetime'], $Tpl, 'entry:loop');
        }
        if( $this->detail_date_on === 'on' ) {
            $vars += $this->buildDate($row['entry_updated_datetime'], $Tpl, 'entry:loop', 'udate#');
            $vars += $this->buildDate($row['entry_posted_datetime'], $Tpl, 'entry:loop', 'pdate#');
            $vars += $this->buildDate($row['entry_start_datetime'], $Tpl, 'entry:loop', 'sdate#');
            $vars += $this->buildDate($row['entry_end_datetime'], $Tpl, 'entry:loop', 'edate#');
        }

        //-----------
        // build new
        if ( strtotime($row['entry_datetime']) + intval($this->newtime) > requestTime() ) {
            $Tpl->add(array('new:touch', 'entry:loop'));    // 後方互換
            $Tpl->add(array('new', 'entry:loop'));
        }

        return true;
    }

    function get()
    {
        $this->initConfig();
        $DB     = DB::singleton(dsn());
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $entryOrder = $this->order;

        if ( 'entry-edit' === ADMIN || 'entry_editor' === ADMIN ) {
            $vars   = array();
            $step   = $this->Post->get('step', 'apply');
            $action = !EID ? 'insert' : 'update';

            if (ADMIN === 'entry_editor' && EID) {
                $Tpl->add(array('revision', 'entry:loop'));
            }

            switch ( $step ) {
                case 'confirm':
                case 'result':
                    $Entry  =& $this->Post->getChild('entry');
                    $Field  =& $this->Post->getChild('field');
                    $Column = array();
                    $_Column    = acmsUnserialize($this->Post->get('column'));

                    foreach ( $_Column as $data ) {
                        $Column[intval($data['sort'])]  = $data;
                    }
                    ksort($Column);
                    $this->buildColumn($Column, $Tpl, $this->eid);
                    $vars   = $this->buildField($Field, $Tpl, 'entry:loop', 'entry');

                    $Tpl->add(array('header#'.$step, 'adminEntryTitle'), array(
                        'adminTitle' => $Entry->get('title'),
                    ));

                    break;
                case 'reapply':
                default:
                    $Tpl->add(array('header#'.$action, 'adminEntryTitle'));
                    $Tpl->add(array('description#'.$action, 'adminEntryTitle'));
            }
            $Tpl->add('adminEntryTitle');
            $Tpl->add('adminEntryEdit');
            $Tpl->add('entry:loop', $vars);

        } else if ( 'form2-edit' == ADMIN ) {
            $Tpl->add('adminFormEdit');
            $Tpl->add('entry:loop');
        } else if ( $this->eid == strval(intval($this->eid)) ) {

            if ( RVID ) {
                $SQL    = SQL::newSelect('entry_rev');
                $SQL->addWhereOpr('entry_rev_id', RVID);
            } else {
                $SQL    = SQL::newSelect('entry');
            }
            $SQL->addWhereOpr('entry_id', $this->eid);

            $q      = $SQL->get(dsn());
            if ( !$row = $DB->query($q, 'row') ) {
                return $this->resultsNotFound($Tpl);
            }
            if ( !IS_LICENSED ) {
                $row['entry_title'] = '[test]'.$row['entry_title'];
            }

            $eid    = $row['entry_id'];

            $vars   = array();

            //---------
            // column
            $break      = null;
            $micropage  = null;
            $micropageLabel = array();
            $RVID_      = RVID;
            if ( !RVID && $row['entry_approval'] === 'pre_approval' ) {
                $RVID_  = 1;
            }
            if ( $Column = loadColumn($eid, null, $RVID_) ) {
                if ( $this->micropager_on === 'on' ) {
                    $break      = 1;
                    $micropage  = $this->page;
                    $_Column    = $Column;
                    $Column     = array();
                    foreach ( $_Column as $col ) {
                        if ( 'break' == $col['type'] ) {
                            if ( $micropage == $break ) {
                                buildUnitData($col['label'], $micropageLabel, 'label');
                            }
                            $break++;
                        }
                        if ( $micropage == $break ) {
                            $Column[]   = $col;
                        }
                    }
                }
                $this->buildColumn($Column, $Tpl, $eid);
            } else {
                $Tpl->add('unit:loop');
                if ( 1
                    and VIEW == 'entry'
                    and 'on' == config('entry_edit_inplace_enable')
                    and 'on' == config('entry_edit_inplace')
                    and ( !enableApproval() || sessionWithApprovalAdministrator() )
                    and $row['entry_approval'] !== 'pre_approval'
                    and !ADMIN
                    and ( 0
                        or roleEntryAuthorization(BID, $row, false)
                        or ( 1
                            and sessionWithContribution()
                            and SUID == ACMS_RAM::entryUser($eid)
                        )
                    )
                ) {
                    $Tpl->add('edit_inplace', array(
                        'class' => 'js-edit_inplace'
                    ));
                }
            }
            // フルテキストのEagerLoading
            if ($this->summary_on === 'on') {
                $this->summaryFulltextEagerLoadingData = Tpl::eagerLoadFullText(array($eid));
            }
            // メインイメージのEagerLoading
            if (config('entry_body_image_on') === 'on') {
                $this->mainImageEagerLoadingData = Tpl::eagerLoadMainImage(array($row));
            }
            // 関連エントリーのEagerLoading
            if ($this->related_entry_on === 'on') {
                $this->relatedEntryEagerLoadingData = Tpl::eagerLoadRelatedEntry(array($eid));
            }
            $this->buildBodyField($Tpl, $vars, $row);

            if ( 1
                && isset($row['entry_form_id'])
                && !empty($row['entry_form_id'])
                && isset($row['entry_form_status'])
                && $row['entry_form_status'] == 'open'
                && config('form_edit_action_direct') == 'on'
            ) {
                $Tpl->add('formBody');
            }
            $Tpl->add('entry:loop', $vars);

            //-----------------
            // build serialNavi
            if($this->serial_navi_on === 'on'){
                $SQLCommon  = SQL::newSelect('entry');
                $SQLCommon->addLeftJoin('category', 'category_id', 'entry_category_id');
                $SQLCommon->setLimit(1);
                $SQLCommon->addWhereOpr('entry_link', ''); // リンク先URLが設定されているエントリーはリンクに含まないようにする
                $SQLCommon->addWhereOpr('entry_blog_id', $this->bid);
                if ($this->serial_navi_ignore_category_on !== 'on') {
                    ACMS_Filter::categoryTree($SQLCommon, $this->cid, $this->categoryAxis());
                }
                ACMS_Filter::entrySession($SQL);
                ACMS_Filter::entrySpan($SQLCommon, $this->start, $this->end);
                if ( !empty($this->tags) ) {
                    ACMS_Filter::entryTag($SQLCommon, $this->tags);
                }
                if ( !empty($this->keyword) ) {
                    ACMS_Filter::entryKeyword($SQLCommon, $this->keyword);
                }
                if ( !empty($this->Field) ) {
                    ACMS_Filter::entryField($SQLCommon, $this->Field);
                }

                $SQLCommon->addWhereOpr('entry_indexing', 'on');
                $aryOrder1 = explode('-', $entryOrder[0]);
                $fd = isset($aryOrder1[0]) ? $aryOrder1[0] : null;
                $sortOrder = isset($aryOrder1[1]) ? $aryOrder1[1] : 'desc';

                if ( 'random' <> $fd ) {
                    switch ( $fd ) {
                        case 'datetime':
                            $field  = 'entry_datetime';
                            $value  = ACMS_RAM::entryDatetime($this->eid);
                            break;
                        case 'updated_datetime':
                            $field  = 'entry_updated_datetime';
                            $value  = ACMS_RAM::entryUpdatedDatetime($this->eid);
                            break;
                        case 'posted_datetime':
                            $field  = 'entry_posted_datetime';
                            $value  = ACMS_RAM::entryPostedDatetime($this->eid);
                            break;
                        case 'code':
                            $field  = 'entry_code';
                            $value  = ACMS_RAM::entryCode($this->eid);
                            break;
                        case 'sort':
                            if ( $this->uid ) {
                                $field  = 'entry_user_sort';
                                $value  = ACMS_RAM::entryUserSort($this->eid);
                            } else if ( $this->cid ) {
                                $field  = 'entry_category_sort';
                                $value  = ACMS_RAM::entryCategorySort($this->eid);
                            } else {
                                $field  = 'entry_sort';
                                $value  = ACMS_RAM::entrySort($this->eid);
                            }
                            break;
                        case 'id':
                        default:
                            $field  = 'entry_id';
                            $value  = $this->eid;
                    }

                    //----------------
                    // build prevLink
                    $SQL    = new SQL_Select($SQLCommon);
                    $W1  = SQL::newWhere();
                    $W1->addWhereOpr($field, $value, '=');
                    $W1->addWhereOpr('entry_id', $this->eid, '<');
                    $W2 = SQL::newWhere();
                    $W2->addWhere($W1);
                    if ($sortOrder === 'desc') {
                        $W2->addWhereOpr($field, $value, '<', 'OR');
                    } else {
                        $W2->addWhereOpr($field, $value, '>', 'OR');
                    }
                    $SQL->addWhere($W2);
                    if ($sortOrder === 'desc') {
                        ACMS_Filter::entryOrder($SQL, array($fd.'-desc', 'id-desc'), $this->uid, $this->cid);
                    } else {
                        ACMS_Filter::entryOrder($SQL, array($fd.'-asc', 'id-asc'), $this->uid, $this->cid);
                    }
                    ACMS_Filter::entrySession($SQL);
                    $q  = $SQL->get(dsn());

                    if ( $row = $DB->query($q, 'row') ) {
                        $Tpl->add('prevLink', array(
                            'name' => addPrefixEntryTitle($row['entry_title']
                                , $row['entry_status']
                                , $row['entry_start_datetime']
                                , $row['entry_end_datetime']
                                , $row['entry_approval']
                            ),
                            'url' => acmsLink(array(
                                '_inherit'  => true,
                                'eid'       => $row['entry_id'],
                            )),
                            'eid' => $row['entry_id'],
                        ));
                    } else {
                        $Tpl->add('prevNotFound');
                    }

                    //----------------
                    // build nextLink
                    $SQL    = new SQL_Select($SQLCommon);
                    $W1  = SQL::newWhere();
                    $W1->addWhereOpr($field, $value, '=');
                    $W1->addWhereOpr('entry_id', $this->eid, '>');
                    $W2 = SQL::newWhere();
                    $W2->addWhere($W1);
                    if ($sortOrder === 'desc') {
                        $W2->addWhereOpr($field, $value, '>', 'OR');
                    } else {
                        $W2->addWhereOpr($field, $value, '<', 'OR');
                    }
                    $SQL->addWhere($W2);
                    if ($sortOrder === 'desc') {
                        ACMS_Filter::entryOrder($SQL, array($fd.'-asc', 'id-asc'), $this->uid, $this->cid);
                    } else {
                        ACMS_Filter::entryOrder($SQL, array($fd.'-desc', 'id-desc'), $this->uid, $this->cid);
                    }
                    ACMS_Filter::entrySession($SQL);
                    $q  = $SQL->get(dsn());

                    if ( $row = $DB->query($q, 'row') ) {
                        $Tpl->add('nextLink', array(
                            'name' => addPrefixEntryTitle($row['entry_title']
                                , $row['entry_status']
                                , $row['entry_start_datetime']
                                , $row['entry_end_datetime']
                                , $row['entry_approval']
                            ),
                            'url' => acmsLink(array(
                                '_inherit'  => true,
                                'eid'       => $row['entry_id'],
                            )),
                            'eid' => $row['entry_id'],
                        ));
                    } else {
                        $Tpl->add('nextNotFound');
                    }
                }

                if($this->micropager_on){
                    //-----------
                    // micropage
                    if ( !empty($micropageLabel) ) {
                        $micropageLabel['url'] = acmsLink(array(
                            '_inherit'  => true,
                            'eid'       => $this->eid,
                            'page'      => $micropage + 1,
                        ));
                        $Tpl->add('micropageLink', $micropageLabel);
                    }

                    //------------
                    // micropager
                    if ( !empty($micropage) ) {
                        $vars       = array();
                        $delta      = $this->micropager_delta;
                        $curAttr    = $this->micropager_cur_attr;
                        $vars       += $this->buildPager($micropage, 1, $break, $delta, $curAttr, $Tpl, 'micropager');
                        $Tpl->add('micropager', $vars);
                    }
                }
                $Tpl->add(null, array('upperUrl' => acmsLink(array(
                    'eid'   => false,
                ))));
            }
        } else {
            $limit  = idval($this->limit);
            $from   = ($this->page - 1) * $limit;
            $SQL    = SQL::newSelect('entry');

            $multiId        = false;
            $BlogSub        = null;
            $CategorySub    = null;

            $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
            $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');

            if ( !empty($this->cid) ) {
                $CategorySub = SQL::newSelect('category');
                $CategorySub->setSelect('category_id');
                if ( is_int($this->cid) ) {
                    ACMS_Filter::categoryTree($CategorySub, $this->cid, $this->categoryAxis());
                } else if ( strpos($this->cid, ',') !== false ) {
                    $CategorySub->addWhereIn('category_id', explode(',', $this->cid));
                    $multiId = true;
                }
                ACMS_Filter::categoryStatus($CategorySub);
            } else {
                ACMS_Filter::categoryStatus($SQL);
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
                ACMS_Filter::entryField($SQL, $this->Field);
            }

            if ( 'on' === $this->indexing ) {
                $SQL->addWhereOpr('entry_indexing', 'on');
            }
            if ( !empty($this->uid) ) {
                if ( is_int($this->uid) ) {
                    $SQL->addWhereOpr('entry_user_id', $this->uid);
                } else if ( strpos($this->uid, ',') !== false ) {
                    $SQL->addWhereIn('entry_user_id', explode(',', $this->uid));
                    $multiId = true;
                }
            }
            if ( !empty($this->eid) && !is_int($this->eid) ) {
                $SQL->addWhereIn('entry_id', explode(',', $this->eid));
                $multiId = true;
            }

            if ( !empty($this->bid) ) {
                $BlogSub = SQL::newSelect('blog');
                $BlogSub->setSelect('blog_id');
                if ( is_int($this->bid) ) {
                    if ( $multiId ) {
                        ACMS_Filter::blogTree($BlogSub, $this->bid, 'descendant-or-self');
                    } else {
                        ACMS_Filter::blogTree($BlogSub, $this->bid, $this->blogAxis());
                    }
                } else if ( strpos($this->bid, ',') !== false ) {
                    $BlogSub->addWhereIn('blog_id', explode(',', $this->bid));
                }
                ACMS_Filter::blogStatus($BlogSub);
            } else {
                ACMS_Filter::blogStatus($SQL);
            }

            //-------------------------
            // filter (blog, category)
            if ( $BlogSub ) {
                $SQL->addWhereIn('entry_blog_id', $DB->subQuery($BlogSub));
            }
            if ($CategorySub) {
                if ('on' === $this->sub_category) {
                    $SUB = SQL::newWhere();
                    $SUB->addWhereIn('entry_category_id', $DB->subQuery($CategorySub), 'OR');

                    $SUB2 = SQL::newSelect('entry_sub_category');
                    $SUB2->addSelect('entry_sub_category_eid');
                    $SUB2->addWhereIn('entry_sub_category_id', $DB->subQuery($CategorySub));
                    $SUB->addWhereIn('entry_id',  $DB->subQuery($SUB2), 'OR');

                    $SQL->addWhere($SUB);
                } else {
                    $SQL->addWhereIn('entry_category_id', $DB->subQuery($CategorySub));
                }

            }

            $Amount = new SQL_Select($SQL);
            $Amount->setSelect('DISTINCT(entry_id)', 'entry_amount', null, 'COUNT');

            $offset = intval($this->offset);
            $_limit = $limit + 1;

            $sortFd = ACMS_Filter::entryOrder($SQL, $entryOrder, $this->uid, $this->cid);
            $SQL->setLimit($_limit, $from + $offset);

            if ( !empty($sortFd) ) {
                $SQL->setGroup($sortFd);
            }
            $SQL->addGroup('entry_id');

            $q      = $SQL->get(dsn());
            $all    = $DB->query($q, 'all');

            $nextPage   = false;
            if ( count($all) > $limit ) {
                array_pop($all);
                $nextPage   = true;
            }

            //------------------
            // not Found
            if ( empty($all) ) {
                return $this->resultsNotFound($Tpl);
            }

            //---------------
            // simple pager
            if ( $this->simple_pager_on === 'on' ) {

                // prev page
                if ( $this->page > 1 ) {
                    $Tpl->add('prevPage', array(
                       'url'    => acmsLink(array(
                            'page' => $this->page - 1,
                        ), true),
                    ));
                } else {
                    $Tpl->add('prevPageNotFound');
                }

                // next page
                if ( $nextPage ) {
                    $Tpl->add('nextPage', array(
                       'url'    => acmsLink(array(
                            'page' => $this->page + 1,
                        ), true),
                    ));
                } else {
                    $Tpl->add('nextPageNotFound');
                }
            }
            $entryIds = array();
            foreach ($all as $entry) {
                $entryIds[] = $entry['entry_id'];
            }

            // フルテキストのEagerLoading
            if ($this->summary_on === 'on') {
                $this->summaryFulltextEagerLoadingData = Tpl::eagerLoadFullText($entryIds);
            }
            // メインイメージのEagerLoading
            if (config('entry_body_image_on') === 'on') {
                $this->mainImageEagerLoadingData = Tpl::eagerLoadMainImage($all);
            }
            // 関連エントリーのEagerLoading
            if ($this->related_entry_on === 'on') {
                $this->relatedEntryEagerLoadingData = Tpl::eagerLoadRelatedEntry($entryIds);
            }

            //------------------
            // build summary tpl
            foreach ( $all as $i => $row ) {
                $serial = ++$i;
                if ( !IS_LICENSED ) $row['entry_title'] = '[test]'.$row['entry_title'];

                $eid    = $row['entry_id'];

                $continueName   = $row['entry_title'];
                $summaryRange   = strval(config('entry_body_fix_summary_range'));
                if ( !strlen($summaryRange) ) $summaryRange = strval($row['entry_summary_range']);
                $summaryRange   = !!strlen($summaryRange) ? intval($summaryRange) : null;
                $inheritUrl = acmsLink(array(
                    'eid'       => $eid,
                ));

                $vars   = array();

                $RVID_      = RVID;
                if ( !RVID && $row['entry_approval'] === 'pre_approval' ) {
                    $RVID_  = 1;
                }

                //---------
                // column
                if ( $this->show_all_index == 'on' ) {
                    $summaryRange = null;
                }

                $SQL = SQL::newSelect('column');
                $SQL->addSelect('*', 'column_amount', null, 'COUNT');
                $SQL->addWhereOpr('column_entry_id', $eid);
                $amount = $DB->query($SQL->get(dsn()), 'one');

                if ( $Column = loadColumn($eid, $summaryRange, $RVID_) ) {
                    $this->buildColumn($Column, $Tpl, $eid);
                    if ( !empty($summaryRange) ) {
                        if ( $summaryRange < $amount ) {
                            $vars['continueUrl']    = $inheritUrl;
                            $vars['continueName']   = $continueName;
                        }
                    }
                } else if ( $amount > 0 ) {
                    $vars['continueUrl']    = $inheritUrl;
                    $vars['continueName']   = $continueName;
                }

                $this->buildBodyField($Tpl, $vars, $row, $serial);
                $Tpl->add('entry:loop', $vars);
            }

            if ( 'random' <> strtolower($entryOrder[0]) and ($this->pager_on === 'on')) {
                $itemsAmount = intval($DB->query($Amount->get(dsn()), 'one'));

                $vars       = array();
                $delta      = intval($this->pager_delta);
                $curAttr    = $this->pager_cur_attr;
                if ( is_numeric($this->offset) ) {
                    $itemsAmount -= $this->offset;
                }
                $vars       += $this->buildPager($this->page, $limit, $itemsAmount, $delta, $curAttr, $Tpl);
            }
            $Tpl->add(null, $vars);
        }

        return $Tpl->get();
    }

    function resultsNotFound($Tpl)
    {
        $Tpl->add('notFound');
        if ( 'on' == $this->notfoundStatus404 ) {
            httpStatusCode('404 Not Found');
        }
        return $Tpl->get();
    }
}
