<?php

class ACMS_POST_Layout extends ACMS_POST
{
    function save($data, $preview = false)
    {
        if (empty($data)) {
            return false;
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newInsert('layout_grid');
        $SQL->addInsert('layout_grid_identifier', $data['identifier']);
        $SQL->addInsert('layout_grid_serial', $data['serial']);
        $SQL->addInsert('layout_grid_class', $data['class']);
        $SQL->addInsert('layout_grid_parent', $data['pid']);
        $SQL->addInsert('layout_grid_col', $data['col']);
        $SQL->addInsert('layout_grid_row', $data['row']);
        $SQL->addInsert('layout_grid_mid', $data['mid']);
        $SQL->addInsert('layout_grid_tpl', $data['tpl']);
        $SQL->addInsert('layout_grid_blog_id', BID);
        if ($preview) {
            $SQL->addInsert('layout_grid_preview', 1);
        }

        $DB->query($SQL->get(dsn()), 'exec');

        return true;
    }
}
