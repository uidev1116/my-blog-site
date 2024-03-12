<?php

class ACMS_GET_Admin extends ACMS_GET
{
    function buildBlogSelect(&$Tpl, $rbid = BID, $selectedBid = null, $loopblock = 'loop', $aryAuth = array(), $isGlobal = false, $order = 'sort-asc')
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('blog');
        if ($isGlobal) {
            ACMS_Filter::blogTree($SQL, $rbid, 'descendant-self', null);
            if (SUID) {
                $SQL->addWhereIn('blog_id', Auth::getAuthorizedBlog(SUID));
            }
        } else {
            $SQL->addWhereOpr('blog_id', $rbid);
        }
        $SQL->addOrder('blog_id');

        list($order, $asc)  = preg_split('/-/', $order);
        $order              = 'blog_' . $order;

        $q      = $SQL->get(dsn());
        $root   = 0;
        if (!!$DB->query($q, 'fetch') and !!($row = $DB->fetch($q))) {
            $all    = array();
            $amount = array();
            $parent = array();
            $last   = array();
            do {
                $bid    = intval($row['blog_id']);
                $pid    = intval($row['blog_parent']);
                $all[$pid][]    = $row;
                $parent[$bid]   = ($rbid != $bid) ? $pid : 0;
                if ($rbid == $bid) {
                    $root = $pid;
                }
                if (!isset($amount[$pid])) {
                    $amount[$pid]  = 0;
                }
                $amount[$pid]   += 1;
            } while (!!($row = $DB->fetch($q)));

            foreach ($all as $i => $v) {
                $sort = array();
                foreach ($all[$i] as $key => $data) {
                    $sort[$key] = $data[$order];
                }
                if ($asc === 'asc') {
                    array_multisort($sort, SORT_ASC, $all[$i]);
                } else {
                    array_multisort($sort, SORT_DESC, $all[$i]);
                }
                $end        = end($all[$i]);
                $last[$i]   = $end['blog_id'];
            }
            $stack  = reset($all);
            $last   = array_flip($last);

            $marks  = configArray('indent_marks');
            $query  = QUERY ? '?' . QUERY : '';
            $query  = preg_replace('/\_bid=(\d+)/', 'prev-bid=$1', $query);
            while ($row = array_shift($stack)) {
                $bid    = intval($row['blog_id']);
                $blocks = array();
                if (isset($parent[$bid]) && isset($parent[$parent[$bid]])) {
                    $blocks[]   = isset($last[$bid]) ? $marks[0] : $marks[1];
                    $_pid   = $bid;
                    while ($_pid = $parent[$_pid]) {
                        if (empty($parent[$_pid])) {
                            break;
                        }
                        $blocks[]   = isset($last[$_pid]) ? $marks[2] : $marks[3];
                    }
                }

                $vars  = array(
                    'value'         => $bid,
                    'code'          => $row['blog_code'],
                    'label'         => $row['blog_name'],
                    'indent'        => join('', array_reverse($blocks)),
                    'adminUrl'      => acmsLink(array('bid' => $bid, 'admin' => 'top')),
                    'adminUrl2'     => acmsLink(array(
                        'bid'   => $bid,
                        'admin' => ADMIN,
                    )) . $query,
                );

                if ($selectedBid == $bid) {
                    $vars['selected']   = config('attr_selected');
                }

                $Tpl->add($loopblock, $vars);

                if (isset($all[$bid])) {
                    while ($_row = array_pop($all[$bid])) {
                        array_unshift($stack, $_row);
                    }
                    unset($all[$bid]);
                }
            }
        }

        return array();
    }

    function buildUserSelect(&$Tpl, $bid = BID, $selectedUid = null, $loopblock = 'loop', $aryAuth = array(), $isGlobal = false, $order = 'sort-asc')
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('user');
        $SQL->addSelect('user_id');
        $SQL->addSelect('user_name');
        $SQL->addWhereOpr('user_pass', '', '<>');

        if ($isGlobal) {
            $SQL->addLeftJoin('blog', 'blog_id', 'user_blog_id');
            $SQL->addWhereOpr('blog_left', ACMS_RAM::blogLeft($bid), '<=');
            $SQL->addWhereOpr('blog_right', ACMS_RAM::blogRight($bid), '>=');
        } else {
            $SQL->addWhereOpr('user_blog_id', $bid);
        }

        if (!empty($aryAuth)) {
            $SQL->addWhereIn('user_auth', $aryAuth);
        }
        $SQL->addOrder('user_blog_id');
        ACMS_Filter::userOrder($SQL, $order);

        foreach ($DB->query($SQL->get(dsn()), 'all') as $row) {
            $uid    = intval($row['user_id']);
            $vars   = array(
                'value' => $uid,
                'label' => $row['user_name'],
            );
            if (intval($selectedUid) == $uid) {
                $vars['selected'] = config('attr_selected');
            }
            $Tpl->add($loopblock, $vars);
        }

        return array();
    }

    /**
     * @param Template $Tpl
     * @param array|bool|int|null|resource $bid
     * @param null $selectedCid
     * @param string|array $loopblock
     * @param bool $isGlobal
     * @param string $order
     * @param int $filterCid
     * @return array
     */
    function buildCategorySelect(&$Tpl, $bid = BID, $selectedCid = null, $loopblock = 'loop', $isGlobal = false, $order = 'sort-asc', $filterCid = 0)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('category');
        $SQL->addSelect('category_id');
        $SQL->addSelect('category_name');
        $SQL->addSelect('category_code');
        $SQL->addSelect('category_status');
        $SQL->addSelect('category_parent');
        $SQL->addSelect('category_left');
        $SQL->addSelect('category_sort');
        $SQL->addLeftJoin('entry', 'entry_category_id', 'category_id');

        if ($isGlobal) {
            $SQL->addLeftJoin('blog', 'blog_id', 'category_blog_id');
            ACMS_Filter::categoryTreeGlobal($SQL, $bid, true, null);
            $SQL->addGroup('category_id');
            $SQL->addOrder('blog_left');
        } else {
            $SQL->addWhereOpr('category_blog_id', $bid);
            $SQL->addGroup('category_id');
        }
        if ($filterCid > 0) {
            ACMS_Filter::categoryTree($SQL, $filterCid, 'descendant');
        }
        if (true) {
            $CaseW  = SQL::newWhere();
            $CaseW->addWhereOpr('entry_blog_id', null, '<>');
            $CaseW->addWhereOpr('entry_status', 'trash', '<>');

            $Case   = SQL::newCase();
            $Case->add($CaseW, 1);
            $Case->setElse('NULL');

            $SQL->addSelect($Case, 'category_entry_amount', null, 'COUNT');
            $SQL->addSelect('entry_status');
        }

        ACMS_Filter::categoryOrder($SQL, $order);

        $q  = $SQL->get(dsn());
        if (!!$DB->query($q, 'fetch') and !!($row = $DB->fetch($q))) {
            $all    = array();
            $amount = array();
            $parent = array();
            $last   = array();
            do {
                $cid    = intval($row['category_id']);
                $pid    = intval($row['category_parent']);
                if ($filterCid > 0 && $filterCid === $pid) {
                    $pid = 0;
                }
                $all[$pid][]    = $row;
                $parent[$cid]   = $pid;
                $last[$pid]     = $cid;
                if (!isset($amount[$pid])) {
                    $amount[$pid]  = 0;
                }
                $amount[$pid]   += 1;
            } while (!!($row = $DB->fetch($q)));

            $stack  = $all[0];
            unset($all[0]);
            $last   = array_flip($last);

            $marks  = configArray('indent_marks');
            while ($row = array_shift($stack)) {
                $cid = intval($row['category_id']);
                $blocks = array();
                if (!empty($parent[$cid])) {
                    $blocks[]   = isset($last[$cid]) ? $marks[0] : $marks[1];
                    $_pid   = $cid;
                    while ($_pid = $parent[$_pid]) {
                        if (empty($parent[$_pid])) {
                            break;
                        }
                        $blocks[]   = isset($last[$_pid]) ? $marks[2] : $marks[3];
                    }
                }

                $vars  = array(
                    'value'     => $cid,
                    'code'      => $row['category_code'],
                    'label'     => $row['category_name'],
                    'indent'    => join('', array_reverse($blocks)),
                );
                if (isset($row['category_entry_amount'])) {
                    $vars['amount'] = $row['category_entry_amount'];
                }
                if ($selectedCid == $cid) {
                    $vars['selected']    = config('attr_selected');
                }

                // $loopblockはarrayのケースと、stringのケースがあるのでキャストしてからarray_merge
                $Tpl->add(array_merge(array('status:touch#' . $row['category_status']), (array)$loopblock));

                $Tpl->add($loopblock, $vars);

                if (isset($all[$cid])) {
                    while ($_row = array_pop($all[$cid])) {
                        array_unshift($stack, $_row);
                    }
                    unset($all[$cid]);
                }
            }
        }

        return array();
    }
}
