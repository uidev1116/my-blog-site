<?php

class ACMS_GET_Admin_Category_Select extends ACMS_GET_Admin
{
    public $_scope  = [
        'cid'   => 'global',
    ];

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(null, $this->buildCategorySelect(
            $Tpl,
            BID,
            $this->cid,
            'loop'
        ));
        return $Tpl->get();
    }
}
