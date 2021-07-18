<?php

class ACMS_GET_Admin_Rule_List extends ACMS_GET
{
    function get()
    {
        $rid    = isset($_GET['rid']) ? idval($_GET['rid']) : null;

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('rule');
        $SQL->addSelect('rule_id');
        $SQL->addSelect('rule_name');
        $SQL->addWhereOpr('rule_blog_id', BID);
        $q  = $SQL->get(dsn());

        if ( !$DB->query($q, 'fetch') ) return '';
        if ( !$row = $DB->fetch($q) ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        do {
            $id     = intval($row['rule_id']);
            $name   = $row['rule_name'];
            $vars   = array(
                'id'    => $id,
                'name'  => $name,
            );
            if ( $rid === $id ) $vars['selected'] = config('attr_selected');
            $Tpl->add('loop', $vars);
        } while ( $row = $DB->fetch($q) );

        return $Tpl->get();
    }
}
