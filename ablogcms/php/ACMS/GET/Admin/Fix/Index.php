<?php

class ACMS_GET_Admin_Fix_Index extends ACMS_GET_Admin
{
    function get()
    {
        if ('fix_index' <> ADMIN) {
            return false;
        }
        if (!sessionWithAdministration()) {
            return false;
        }

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $aryAdmin = array(
            'fix_image',
            'fix_unit-size',
            'fix_unit-group',
            'fix_unit-map',
            'fix_sequence',
            'fix_fulltext',
            'fix_ngram',
            'fix_tag',
            'fix_replacement',
        );
        foreach ($aryAdmin as $admin) {
            $AP     = array(
                'bid'   => BID,
                'admin' => $admin,
            );
            $Tpl->add($admin, array(
                'url'   => acmsLink($AP),
            ));
        }

        return $Tpl->get();
    }
}
