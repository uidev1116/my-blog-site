<?php

class ACMS_GET_Admin_Category_Assist extends ACMS_GET_Admin
{
    public $_scope = [
        'cid' => 'global',
        'eid' => 'global',
        'keyword' => 'global',
    ];

    function get()
    {
        if (!sessionWithContribution()) {
            die('{}');
        }
        $filterCid = 0;
        if ($this->Get->get('narrowDown') === 'true') {
            $filterCid = intval(config('entry_edit_category_filter', 0));
        }
        $order = 'sort-desc';
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
        /** @var array{ label: string, value: int }[] */
        $list = [];
        $DB = DB::singleton(dsn());
        if (!!$DB->query($q, 'fetch') and !!($row = $DB->fetch($q))) {
            $all = [];
            $parent = [];
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

                $blocks = [];
                $_pid = $cid;
                while ($_pid = $parent[$_pid]) {
                    array_unshift($blocks, $category[$_pid]);
                    if (empty($parent[$_pid])) {
                        break;
                    }
                }
                $blocks[] = $row;
                $label = '';
                foreach ($blocks as $i => $item) {
                    if ($i > 0) {
                        $label .= ' > ';
                    }
                    $label .= $item['category_name'];
                }
                $list[] = [
                    'label' => $label,
                    'value' => $cid,
                ];
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
        $currentCid = (int)$this->Get->get('currentCid');
        if ($currentCid > 0) {
            if (array_search(intval($currentCid), array_column($list, 'value'), true) === false) {
                $name = ACMS_RAM::categoryName($currentCid);
                $tempCid = $currentCid;
                do {
                    $tempCid = ACMS_RAM::categoryParent($tempCid);
                    if (empty($tempCid)) {
                        break;
                    }
                    $name = ACMS_RAM::categoryName($tempCid) . ' > ' . $name;
                } while (true);
                $list[] = [
                    'label' => $name,
                    'value' => $currentCid,
                ];
            }
        }
        return $list;
    }
}
