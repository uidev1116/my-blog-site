<?php

class ACMS_GET_Admin_Category_SelectGlobal extends ACMS_GET_Admin
{
    var $_scope  = array(
        'cid' => 'global',
        'eid' => 'global',
    );

    function get()
    {
        if ( !sessionWithContribution() || (!ADMIN && !is_ajax()) ) return '';

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        if (in_array(ADMIN, array('entry-edit', 'entry_editor', 'entry-add'))) {
            $target_bid = $this->bid;
        } else {
            $target_bid = $this->Get->get('_bid', $this->bid);
        }
        if (!$target_bid) {
            $target_bid = BID;
        }

        $order  = 'sort-asc';
        $order2 = config('category_select_global_order');
        if ( !empty($order2) ) {
            $order  = $order2;
        }
        $cid = $this->cid;
        $defaultCid = intval(config('entry_edit_category_default', 0));
        if ($defaultCid > 0) {
            $cid = $defaultCid;
        }
        $filterCid = 0;
        if (ADMIN !== 'config_edit') {
            $filterCid = intval(config('entry_edit_category_filter', 0));
        }

        if (intval($this->eid) > 0) {
            $cid = ACMS_RAM::entryCategory($this->eid);
        }

        $Tpl->add(null, $this->buildCategorySelect(
            $Tpl,
            $target_bid,
            $cid,
            'loop',
            true,
            $order,
            $filterCid
        ));
        return $Tpl->get();
    }
}
