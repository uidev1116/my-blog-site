<?php

class ACMS_GET_Category_Field extends ACMS_GET
{
    var $_scope = array(
        'cid'   => 'global',
    );

    function get()
    {
        if ( !$this->cid ) return '';
        if ( !$row = ACMS_RAM::category($this->cid) ) return '';

        $status = ACMS_RAM::categoryStatus($this->cid);
        if (!sessionWithAdministration() and 'close' === $status) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        $Field  = loadCategoryField($this->cid);
        foreach ( $row as $key => $val ) {
            $Field->setField(preg_replace('@^category_@', '', $key), $val);
        }

        $Geo = loadGeometry('cid', $this->cid);
        if ($Geo) {
            $Tpl->add('geometry', $this->buildField($Geo, $Tpl, null, 'geometry'));
        }

        $Tpl->add(null, $this->buildField($Field, $Tpl));

        return $Tpl->get();
    }
}
