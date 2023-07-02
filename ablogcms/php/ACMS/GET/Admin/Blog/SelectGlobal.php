<?php

class ACMS_GET_Admin_Blog_SelectGlobal extends ACMS_GET_Admin
{
    var $_scope  = array(
        'bid'   => 'global',
    );

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $target_bid = $this->Get->get('bid', $this->bid);
        $Tpl->add(null, $this->buildBlogSelect($Tpl
            , SBID, $target_bid, 'loop', true, true
        ));
        return $Tpl->get();
    }
}
