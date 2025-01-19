<?php

class ACMS_GET_Admin_SubCategory_Assist extends ACMS_GET_Admin_Category_Assist
{
    function get()
    {
        if (!sessionWithContribution()) {
            die('{}');
        }
        $filterCid = intval(config('entry_edit_sub_category_filter', 0));
        $order = 'sort-asc';
        $order2 = config('category_select_global_order');
        if (!empty($order2)) {
            $order = $order2;
        }
        $limit = (int)config('category_select_limit', 999);
        $q = $this->buildQuery($order, $filterCid, $limit);
        $list = $this->buildList($q, $filterCid);
        die(json_encode($list));
    }
}
