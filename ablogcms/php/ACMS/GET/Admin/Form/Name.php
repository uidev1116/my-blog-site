<?php

class ACMS_GET_Admin_Form_Name extends ACMS_GET
{
    function get()
    {
        if ( !$fmid = intval($this->Get->get('fmid')) ) return '';
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('form');
        $SQL->setSelect('form_name');
        $SQL->addWhereOpr('form_id', $fmid);
        $SQL->addWhereOpr('form_blog_id', BID);
        if ( !$name = $DB->query($SQL->get(dsn()), 'one') ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(null, array('fmid' => $fmid, 'name' => $name));
        return $Tpl->get();
    }
}
