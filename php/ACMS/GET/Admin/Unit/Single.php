<?php

class ACMS_GET_Admin_Unit_Single extends ACMS_GET_Admin_Unit
{
    function get()
    {
        if ( 'entry-update-unit' <> substr(ADMIN, 0, 17) ) return '';
        if ( !sessionWithContribution() ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $aryTypeLabel    = array();
        foreach ( configArray('column_add_type') as $i => $type ) {
            $aryTypeLabel[$type]    = config('column_add_type_label', '', $i);
        }

        // URLからユニットタイプを取得
        $type = substr(ADMIN, 18);
        // 特定指定子を含むユニットタイプ
        $actualType = $type;

        // ADD
        if ( !empty($type) )
        {
            $Unit   = new ACMS_Model_Unit(UTID);
            $sort   = $Unit->get('column_sort');
            $pos    = $this->Get->get('pos', 'below');

            $data   = Tpl::getAdminColumnDefinition('add_'.$type, $type, 0);

            $data['id']     = uniqueString();
            $data['clid']   = '';
            $data['type']   = $type;
            if ( $pos == 'below' ) {
                $data['sort']   = ($sort+1);
            } else {
                $data['sort']   = $sort;
            }

            $data['align']  = config('column_def_add_'.$type.'_align', '');
            $data['group']  = config('column_def_add_'.$type.'_group', '');
            $data['attr']   = config('column_def_add_'.$type.'_attr', '');
            $data['size']   = config('column_def_add_'.$type.'_size', '');
            $data['edit']   = config('column_def_add_'.$type.'_edit', '');
            if ($actualType === 'media') {
                $data['link']   = '';
            }
        }
        // UPDATE
        elseif ( !!UTID )
        {
            $Unit   = new ACMS_Model_Unit(UTID);

            // UTIDからユニットタイプを取得
            $type = $Unit->get('column_type');
            // 特定指定子を含むユニットタイプ
            $actualType = detectUnitTypeSpecifier($type);

            $data           = $Unit->getTypeOfData($actualType);
            $data['id']     = UTID;
            $data['clid']   = $Unit->get('column_id');
            $data['type']   = $Unit->get('column_type');
            $data['sort']   = $Unit->get('column_sort');
            $data['align']  = $Unit->get('column_align');
            $data['group']  = $Unit->get('column_group');
            $data['size']   = $Unit->get('column_size');
            $data['attr']   = $Unit->get('column_attr');
            $data['edit']   = '';

            if ($actualType === 'image' || $actualType === 'media') {
                $data['primaryImage'] = ACMS_RAM::entryPrimaryImage(EID);
            }
            if ($actualType === 'media') {
                $data['link'] = $Unit->get('column_field_7');
            }
        }

        // TODO issue: Notice undfined variable data in が出ることがある
        if ( !$this->buildUnit($data, $Tpl) ) return false;

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
//        $classes = configArray('unit_group_class');
//        $labels  = configArray('unit_group_label');
//        foreach ( $labels as $i => $label ) {
//            $Tpl->add('group:loop', array(
//                 'value' => $classes[$i],
//                 'label' => $label,
//                 'selected' => ($classes[$i] === $data['group']) ? config('attr_selected') : '',
//            ));
//        }

        //------
        // attr
        if ( $aryAttr = configArray('column_'.$data['type'].'_attr') ) {
            foreach ( $aryAttr as $i => $_attr ) {
                $label  = config('column_'.$data['type'].'_attr_label', '', $i);
                $_vars  = array(
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
            'cltype'    => $data['type'],
            'uniqid'    => $data['id'],
            'clname'    => ite($aryTypeLabel, $data['type']),
            'clid'      => $data['clid'],
        ));

        //-----------------------
        // add keep sort & gorup
        $Tpl->add(null, array(
            'group' => $data['group'],
            'sort'  => $data['sort'],
            'post'  => implode('/', $_POST),
        ));

        return $Tpl->get();
    }
}
