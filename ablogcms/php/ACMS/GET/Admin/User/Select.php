<?php

class ACMS_GET_Admin_User_Select extends ACMS_GET_Admin
{
    public $_scope = array(
        'uid'   => 'global',
    );
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(null, $this->buildUserSelect(
            $Tpl,
            BID,
            $this->uid,
            'loop',
            array('administrator', 'editor', 'contributor'),
            false,
            'sort-asc'
        ));
        return $Tpl->get();
    }
}
