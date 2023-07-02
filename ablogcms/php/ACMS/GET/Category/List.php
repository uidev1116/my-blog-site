<?php

class ACMS_GET_Category_List extends ACMS_GET
{
    var $_axis  = array(
        'cid'   => 'descendant-or-self',
        'bid'   => 'descendant-or-self',
    );

    function getAncestorsMap($Map, $root, & $i=0)
    {
        foreach ( $Map as $c => $p ) {
            if ( !isset($Map[$p]) && $p !== $root ) {
                $Front      = array();
                $Back       = array();
                $Tmp[$p]    = ACMS_RAM::categoryParent($p);

                $j = 0;
                foreach ( $Map as $c_ => $p_ ) {
                    if ( $j < $i ) $Front[$c_] = $p_;
                    $j++;
                }
                $j = 0;
                foreach ( $Map as $c_ => $p_ ) {
                    if ( $j >= $i ) $Back[$c_] = $p_;
                    $j++;
                }
                $Map = $Front + $this->getAncestorsMap($Tmp, $root, $k) + $Back;
                $i   += $k;
            }
            $i++;
        }
        return $Map;
    }

    function get()
    {
        $categoryIds = array();

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('category');
        $SQL->addSelect('category_id');
        $SQL->addSelect('category_code');
        $SQL->addSelect('category_name');
        $SQL->addSelect('category_parent');
        $SQL->addSelect('category_left');
        $SQL->addSelect('category_indexing');
        $SQL->addLeftJoin('entry', 'entry_category_id', 'category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'category_blog_id');

        ACMS_Filter::blogTree($SQL, $this->bid, 'ancestor-or-self');
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);
        if ( !empty($this->keyword) ) {
            ACMS_Filter::categoryKeyword($SQL, $this->keyword);
        }
        if ( !empty($this->Field) ) {
            if ( config('category_list_field_search') == 'entry' ) {
                ACMS_Filter::entryField($SQL, $this->Field);
            } else {
                ACMS_Filter::categoryField($SQL, $this->Field);
            }
        }
        if (empty($this->cid) && $this->categoryAxis() === 'self') {
            $SQL->addWhereOpr('category_parent', 0);
        }
        $Where  = SQL::newWhere();
        $Where->addWhereOpr('category_blog_id', $this->bid, '=', 'OR');
        $Where->addWhereOpr('category_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);

        $Where  = SQL::newWhere();
        ACMS_Filter::entrySession($Where);
        ACMS_Filter::entrySpan($Where, $this->start, $this->end);
        $Where->addWhereOpr('entry_blog_id', $this->bid);

        $Case   = SQL::newCase();
        $Case->add($Where, 1);
        $Case->setElse('NULL');
        $SQL->addSelect($Case, 'category_entry_amount', null, 'count');
        $SQL->setGroup('category_id');

        if (!($all = $DB->query($SQL->get(dsn()), 'all'))) return '';

        //-------------
        // restructure
        foreach ($all as $row) {
            $cid = intval($row['category_id']);
            $categoryIds[] = $cid;
            foreach ($row as $key => $val) {
                $All[$key][$cid] = $val;
            }
            $All['all_amount'][$cid]    = intval($All['category_entry_amount'][$cid]);
        }
        $All['all_amount'][0] = 0;

        //--------------------------
        // indexing ( swap parent )
        while ( !!($cid = intval(array_search('off', $All['category_indexing']))) ) {
            while ( !!($_cid = intval(array_search($cid, $All['category_parent']))) ) {
                $All['category_parent'][$_cid]  = $All['category_parent'][$cid];
            }
            foreach ( $All as $key => $val ) {
                unset($val[$cid]);
                $All[$key]  = $val;
            }
        }

        //---------------
        // eager loading
        $eagerLoadingCategoryFields = false;
        if (config('category_list_field') === 'on') {
            $eagerLoadingCategoryFields = eagerLoadField($categoryIds, 'cid');
        }

        //------
        // sort
        foreach ( $All as $key => $val ) {
            ksort($val);
            asort($val);
            $All[$key]  = $val;
        }

        //------------
        // all amount
        arsort($All['category_left']);
        foreach ( $All['category_left'] as $cid => $kipple ) {
            $pid    = intval($All['category_parent'][$cid]);
            if ( !isset($All['all_amount'][$pid]) ) $pid = 0;
            $All['all_amount'][$pid] += intval($All['all_amount'][$cid]);
        }
        unset($All['all_amount'][0]);
        asort($All['all_amount']);
        asort($All['category_left']);

        //-----------------------------
        // amount zero ( swap parent )
        if ( 'on' <> config('category_list_amount_zero') ) {
            while ( !!($cid = array_search(0, $All['all_amount'])) ) {
                while ( !!($_cid = intval(array_search($cid, $All['category_parent']))) ) {
                    $All['category_parent'][$_cid]  = $All['category_parent'][$cid];
                }
                foreach ( $All as $key => $val ) {
                    unset($val[$cid]);
                    $All[$key]  = $val;
                }
            }
        }

        //-------
        // order
        $s      = explode('-', config('category_list_order'));
        $order  = isset($s[0]) ? $s[0] : 'id';
        $isDesc = isset($s[1]) ? ('desc' == $s[1]) : false;
        switch ( $order ) {
            case 'amount':
                $key    = 'all_amount';
                break;
            case 'sort':
                $key    = 'category_left';
                break;
            case 'code':
                $key    = 'category_code';
                break;
            default:
                $key    = 'category_id';
        }
        if ( $isDesc ) arsort($All[$key]);

        $Map    = array();
        foreach ( $All[$key] as $cid => $kipple ) {
            $Map[$cid]  = intval($All['category_parent'][$cid]);
        }

        //-------
        // stack
        $root   = ACMS_RAM::categoryParent($this->cid) ? intval(ACMS_RAM::categoryParent($this->cid)) : 0;
        $stack  = array($root);

        //-------------------------
        // restructure (ancestors)
        $Map    = $this->getAncestorsMap($Map, $root);

        if ( empty($Map) ) return '';

        //-------
        // tpl
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $Tpl->add('ul#front');
        $Tpl->add('category:loop');

        //-----------
        // protocol
        $protocol   = HTTPS ? 'https' : 'http';

        //-------
        // level
        $level  = intval(config('category_list_level'));
        if ( empty($level) ) $level = 1000;
        $level--;

        //------------
        // descendant
        $descendant = $this->categoryAxis() === 'descendant';
        $countValues = array_count_values($Map);
        $indexValues = array();
        while ( count($stack) ) {
            $pid = array_pop($stack);
            if (!isset($indexValues[$pid])) {
                $indexValues[$pid] = 0;
            }
            $count = isset($countValues[$pid]) ? $countValues[$pid] : 0;
            while (!!($cid = array_search($pid, $Map))) {
                unset($Map[$cid]);

                if ( !isset($All['category_id'][$cid]) ) {
                    if ( !!array_search($cid, $Map) ) {
                        $stack[] = $pid;
                        $stack[] = $cid;
                        $level++;
                        continue 2;
                    }
                }

                $depth  = count($stack) + 1;

                if ( isset($All['category_id'][$cid]) ) {
                    $domain = blogDomain($this->bid);
                    $url    = acmsLink(array(
                        'bid'   => $this->bid,
                        'cid'   => $cid,
                    ));
                    $vars   = array(
                        'bid'       => $this->bid,
                        'cid'       => $cid,
                        'ccd'       => $All['category_code'][$cid],
                        'name'      => $All['category_name'][$cid],
                        'amount'    => $All['all_amount'][$cid],
                        'singleAmount'  => $All['category_entry_amount'][$cid],
                        'level'     => $depth,
                        'url'       => $url,
                        'category:loop.class' => config('category_list_loop_class'),
                    );

                    if (config('category_list_geolocation_on') === 'on') {
                        $Geo = loadGeometry('cid', $cid, null, $this->bid);
                        if ($Geo) {
                            $vars   += $this->buildField($Geo, $Tpl, null, 'geometry');
                        }
                    }

                    if ( 'on' <> config('category_list_amount') ) unset($vars['amount']);
                    if ( CID == $cid ) {
                        $vars['selected']   = config('attr_selected');
                    }

                    //-------
                    // field
                    if ($eagerLoadingCategoryFields && isset($eagerLoadingCategoryFields[$cid])) {
                        $vars += $this->buildField($eagerLoadingCategoryFields[$cid], $Tpl);
                    }

                    $Tpl->add(array('li#front', 'category:loop'), $vars);

                    //------
                    // glue
                    $indexValues[$pid]++;
                    if ($indexValues[$pid] < $count) {
                        $Tpl->add(array('glue', 'category:loop'));
                    }

                    $Tpl->add('category:loop', $vars);
                } else {
                    $Tpl->add(array('li#front', 'category:loop'));
                    $Tpl->add('category:loop');
                }

                if ( $level >= $depth ) {
                    if ( !!array_search($cid, $Map) ) {
                        $Tpl->add(array('ul#front', 'category:loop'));
                        $Tpl->add('category:loop');
                        $stack[] = $pid;
                        $stack[] = $cid;
                        continue 2;
                    }
                }

                $Tpl->add(array('li#rear', 'category:loop'));
                $Tpl->add('category:loop');
            }

            if (!$descendant || count($stack) !== 1) {
                if (count($stack) > 0) {
                    $Tpl->add(array('ul#rear:glue', 'ul#rear', 'category:loop'));
                }
                $Tpl->add(array('ul#rear', 'category:loop'));
                $Tpl->add('category:loop');
                if ( !empty($stack) ) {
                    $Tpl->add(array('li#rear', 'category:loop'));
                    $Tpl->add('category:loop');
                }
            }
        }

        return $Tpl->get();
    }
}
