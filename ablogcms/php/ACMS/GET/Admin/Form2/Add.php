<?php

class ACMS_GET_Admin_Form2_Add extends ACMS_GET_Admin_Entry
{
    function get()
    {
        if ('entry-add' <> substr(ADMIN, 0, 9)) {
            return '';
        }
        if (!sessionWithContribution()) {
            return '';
        }

        $addType    = substr(ADMIN, 10);

        $aryTypeLabel    = [];
        foreach (configArray('column_form_add_type') as $i => $type) {
            $aryTypeLabel[$type]    = config('column_form_add_type_label', '', $i);
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if (!!EID) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('column');
            $SQL->addSelect('*', null, null, 'count');
            $SQL->addWhereOpr('column_attr', 'acms-form');
            $SQL->addWhereOpr('column_entry_id', EID);
            $offset = intval($DB->query($SQL->get(dsn()), 'one'));
        } else {
            $offset = 0;
        }
        $cnt    = 1 + $offset;

        $data['type']   = $addType;
        $data['id']     = uniqueString();

        $this->buildFormColumn($data, $Tpl);

        //------
        // sort
        for ($j = 1; $j <= $cnt; $j++) {
            $_vars  = [
                'value' => $j,
                'label' => $j,
            ];
            if (($i + 1 + $offset) == $j) {
                $_vars['selected']  = config('attr_selected');
            }
            $Tpl->add('sort:loop', $_vars);
        }

        //--------
        // option
        for ($i = 0; $i < 3; $i++) {
            $Tpl->add(['option:loop'], [
                'id'    => $data['id'],
                'unique' => 'new-' . ($i + 1),
            ]);
        }

        $Tpl->add('column:loop', [
            'cltype'    => $addType,
            'uniqid'    => $data['id'],
            'clname'    => ite($aryTypeLabel, $addType),
        ]);

        return $Tpl->get();
    }
}
