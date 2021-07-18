<?php

class ACMS_GET_Admin_Entry_Add extends ACMS_GET_Admin_Entry
{
    function get()
    {
        if ( 'entry-add' <> substr(ADMIN, 0, 9) ) return '';
        if ( !sessionWithContribution() ) return '';

        $addType = substr(ADMIN, 10);

        if ( !$aryType = configArray('column_def_add_' . $addType . '_type') ) return '';

        $aryTypeLabel = array();
        foreach ( configArray('column_add_type') as $i => $type ) {
            $aryTypeLabel[$type] = config('column_add_type_label', '', $i);
        }
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        if ( !!EID ) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('column');
            $SQL->addSelect('*', null, null, 'count');
            $SQL->addWhereOpr('column_attr', 'acms-form', '<>');
            $SQL->addWhereOpr('column_entry_id', EID);
            $offset = intval($DB->query($SQL->get(dsn()), 'one'));
        } else {
            $offset = 0;
        }

        if ($this->Get->get('limit')) {
          $offset = intval($this->Get->get('limit'));
        }

        $cnt = count($aryType) + $offset;
        foreach ( $aryType as $i => $type ) {
            $data = Tpl::getAdminColumnDefinition('add_' . $addType, $type, $i);


            $data['type'] = $type;
            $data['id'] = uniqueString();
            $data['align'] = config('column_def_add_' . $addType . '_align', '', $i);
            $data['group'] = config('column_def_add_' . $addType . '_group', '', $i);
            $data['attr'] = config('column_def_add_' . $addType . '_attr', '', $i);
            $data['size'] = config('column_def_add_' . $addType . '_size', '', $i);
            $data['edit'] = config('column_def_add_' . $addType . '_edit', '', $i);

            if ( !$this->buildColumn($data, $Tpl) ) continue;

            //------
            // sort
            for ( $j = 1; $j <= $cnt; $j++ ) {
                $_vars = array(
                    'value' => $j,
                    'label' => $j,
                );
                if ( ($i + 1 + $offset) == $j ) $_vars['selected'] = config('attr_selected');
                $Tpl->add('sort:loop', $_vars);
            }

            //-------
            // align
            if ( in_array(detectUnitTypeSpecifier($type), array('text', 'custom', 'module', 'table')) ) {
                $Tpl->add(array('align#liquid'), array(
                    'align:selected#'.$data['align'] => config('attr_selected')
                ));
            } else {
                $Tpl->add(array('align#solid'), array(
                    'align:selected#'.$data['align'] => config('attr_selected')
                ));
            }

            //-------
            // group
            if ( 'on' === config('unit_group') ) {
                $labels = configArray('unit_group_label');
                foreach ( $labels as $i => $label ) {
                    $class = config('unit_group_class', '', $i);
                    $Tpl->add('group:loop', array(
                        'value' => $class,
                        'label' => $label,
                        'selected' => ($class === $data['group']) ? config('attr_selected') : '',
                    ));
                }
            }

            //------
            // attr
            if ( $aryAttr = configArray('column_' . $type . '_attr') ) {
                foreach ( $aryAttr as $i => $_attr ) {
                    $label = config('column_' . $type . '_attr_label', '', $i);
                    $_vars = array(
                        'value' => $_attr,
                        'label' => $label,
                    );
                    if ( $data['attr'] == $_attr ) $_vars['selected'] = config('attr_selected');
                    $Tpl->add('clattr:loop', $_vars);
                }
            } else {
                $Tpl->add('clattr#none');
            }


            $Tpl->add('column:loop', array(
                'cltype' => $type,
                'uniqid' => $data['id'],
                'clname' => ite($aryTypeLabel, $type),
            ));
        }
        return $Tpl->get();
    }
}
