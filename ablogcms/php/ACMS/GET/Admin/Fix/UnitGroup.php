<?php

class ACMS_GET_Admin_Fix_UnitGroup extends ACMS_GET_Admin_Fix
{
    function fix(& $Tpl, $block)
    {
        if ( !sessionWithAdministration() ) return false;

        $DB     = DB::singleton(dsn());
        $Fix    =& $this->Post->getChild('fix');
        $target = $Fix->get('unit_group_target');

        //------------
        // unit group
        $SQL    = SQL::newSelect('column');
        $SQL->addSelect('column_group', null, null, 'DISTINCT');
        $SQL->addWhereOpr('column_blog_id', BID);

        $all    = $DB->query($SQL->get(dsn()), 'all');
        foreach ( $all as $group ) {
            $name = $group['column_group'];
            if ( empty($name) ) continue;
            $vars = array(
                'name' => $name,
            );
            $Tpl->add(array_merge(array('unit_group:loop'), $block), $vars);
        }
        return true;
    }
}
