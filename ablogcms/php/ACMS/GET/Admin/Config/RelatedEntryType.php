<?php

class ACMS_GET_Admin_Config_RelatedEntryType extends ACMS_GET
{
    public function get()
    {
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $sql = SQL::newSelect('config');
        $sql->addWhereIn('config_key', array('related_entry_type', 'related_entry_label'));
        $sql->addOrder('config_set_id', 'ASC');
        $sql->addOrder('config_blog_id', 'ASC');
        $sql->addOrder('config_sort', 'ASC');

        $types = array();
        $labels = array();
        $all = DB::query($sql->get(dsn()), 'all');
        foreach ($all as $item) {
            if ($item['config_key'] === 'related_entry_type') {
                $types[] = $item['config_value'];
            }
            if ($item['config_key'] === 'related_entry_label') {
                $labels[] = $item['config_value'];
            }
        }
        foreach ($types as $i => $type) {
            $label = isset($labels[$i]) ? $labels[$i] : '';
            if (empty($label)) {
                continue;
            }
            $tpl->add('related_entry_group:loop', array(
                'related_entry_type' => $type,
                'related_entry_label' => $label,
            ));
        }
        $tpl->add(null, array());
        return $tpl->get();
    }
}
