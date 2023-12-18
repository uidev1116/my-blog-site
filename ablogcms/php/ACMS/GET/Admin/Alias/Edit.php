<?php

class ACMS_GET_Admin_Alias_Edit extends ACMS_GET_Admin_Edit
{
    function edit(& $Tpl)
    {
        $Alias  =& $this->Post->getChild('alias');

        if ( $Alias->isNull() ) {
            if ( $aid = intval($this->Get->get('aid')) ) {
                $Alias->overload(loadAlias($aid));
            } else {
                $Alias->set('status', 'open');
                $Alias->set('indexing', 'on');
            }
        }

        return true;
    }
}
