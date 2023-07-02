<?php

class ACMS_GET_Admin_Category_Assist extends ACMS_GET_Admin
{
    var $_scope = array(
        'cid' => 'global',
        'eid' => 'global',
        'keyword' => 'global',
    );

    function get()
    {
        if (!sessionWithContribution()) {
            die('{}');
        }
        $filterCid = 0;
        if ($this->Get->get('narrowDown') === 'true') {
            $filterCid = intval(config('entry_edit_category_filter', 0));
        }
        $order = 'sort-asc';
        $order2 = config('category_select_global_order');
        if (!empty($order2)) {
            $order = $order2;
        }
        $q = $this->buildQuery($order, $filterCid);
        $list = $this->buildList($q, $filterCid);
        die(json_encode($list));
    }

    protected function buildQuery($order, $filterCid)
    {
        $SQL = SQL::newSelect('category');
        $SQL->addLeftJoin('blog', 'blog_id', 'category_blog_id');
        ACMS_Filter::categoryTreeGlobal($SQL, BID, true, null);
        if ($filterCid > 0) {
            ACMS_Filter::categoryTree($SQL, $filterCid, 'descendant');
        }
        $SQL->addGroup('category_id');
        $SQL->addOrder('blog_left');
        $SQL->setLimit(200);

        if (!!$this->keyword) {
            $DB = DB::singleton(dsn());
            $TEMP = clone $SQL;
            $TEMP->addWhereOpr('category_name', '%' . $this->keyword . '%', 'LIKE', 'AND');
            $all = $DB->query($TEMP->get(dsn()), 'all');
            $WHERE = SQL::newWhere();
            $ancestorCheck = false;
            foreach ($all as $category) {
                $ancestorCheck = true;
                $l = $category['category_left'];
                $r = $category['category_right'];
                $ANCESTOR = SQL::newWhere();
                $ANCESTOR->addWhereOpr('category_left', $l, '<=', 'AND');
                $ANCESTOR->addWhereOpr('category_right', $r, '>=', 'AND');
                $WHERE->addWhere($ANCESTOR, 'OR');
            }
            if ($ancestorCheck) {
                $SQL->addWhere($WHERE, 'AND');
            }
        }
        ACMS_Filter::categoryOrder($SQL, $order);

        return $SQL->get(dsn());
    }

    protected function buildList($q, $filterCid)
    {
        $list = array();
        $DB = DB::singleton(dsn());
        if ( !!$DB->query($q, 'fetch') and !!($row = $DB->fetch($q)) ) {
            $all = array();
            $parent = array();
            do {
                $cid = intval($row['category_id']);
                $pid = intval($row['category_parent']);
                if ($filterCid > 0 && $filterCid === $pid) {
                    $pid = 0;
                }
                $all[$pid][] = $row;
                $parent[$cid] = $pid;
                $category[$cid] = $row;
            } while (!!($row = $DB->fetch($q)));

            $stack = $all[0];
            unset($all[0]);

            while ($row = array_shift($stack)) {
                $cid = intval($row['category_id']);

                $blocks = array();
                $_pid = $cid;
                while ($_pid = $parent[$_pid]) {
                    array_unshift($blocks, $category[$_pid]);
                    if ( empty($parent[$_pid]) ) break;
                }
                $blocks[] = $row;
                $label = '';
                foreach ($blocks as $i => $item) {
                    if ($i > 0) {
                        $label .= ' > ';
                    }
                    $label .= $item['category_name'];
                }
                $list[] = array(
                    'label' => $label,
                    'value' => $cid,
                );
                if (isset($row['category_entry_amount'])) {
                    $vars['amount'] = $row['category_entry_amount'];
                }
                if (isset($all[$cid])) {
                    while ($_row = array_pop($all[$cid])) {
                        array_unshift($stack, $_row);
                    }
                    unset($all[$cid]);
                }
            }
        }
        if ($currentCid = $this->Get->get('currentCid')) {
            if (array_search(intval($currentCid), array_column($list, 'value')) === false) {
                $name = ACMS_RAM::categoryName($currentCid);
                $tempCid = $currentCid;
                do {
                    $tempCid = ACMS_RAM::categoryParent($tempCid);
                    if (empty($tempCid)) {
                        break;
                    }
                    $name = ACMS_RAM::categoryName($tempCid) . ' > ' . $name;
                } while (true);
                $list[] = array(
                    'label' => $name,
                    'value' => $currentCid,
                );
            }
        }
        return $list;
    }
}
