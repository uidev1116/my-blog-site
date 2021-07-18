<?php

class ACMS_GET_Form2_Unit extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        if ( 'form-edit' == ADMIN ) return false;

        $eid = (!!$this->eid) ? $this->eid : EID;
        if ( empty($eid) ) {
            $eid  = $this->Post->get('eid');
        }
        if ( !defined('FORM_ENTRY_ID') && !!$eid ) {
            define('FORM_ENTRY_ID', $eid);
        }

        $Unit   = loadFormUnit($eid);
        $this->buildFormUnit($Unit, $Tpl, $eid);

        return $Tpl->get();
    }

    function buildFormUnit(& $Unit, & $Tpl, $eid)
    {
        foreach ( $Unit as $i => $data ) {
            $type   = $data['type'];
            $sort   = $data['sort'];
            $utid   = $data['clid'];

            $vars   = array(
                'label'     => $data['label'],
                'caption'   => $data['caption'],
            );
            $vars['utid']       = $utid;
            $vars['unit_eid']   = $eid;

            //----------------
            // text, textarea
            if ( in_array($type, array('text', 'textarea')) ) {
                if ( empty($data['label']) ) continue;
            //-------------------------
            // radio, select, checkbox
            } else if ( in_array($type, array('radio', 'select', 'checkbox')) ) {
                if ( 1
                    && isset($data['values']) 
                    && $values = acmsUnserialize($data['values'])
                ) {
                    if ( is_array($values) ) {
                        foreach ( $values as $i => $val ) {
                            if ( !empty($val) ) {
                                $Tpl->add(array($type.'#val:loop', $type, 'column:loop'), array(
                                    'i'     => ++$i,
                                    'value' => $val,
                                    'utid'  => $utid,
                                ));
                            }
                        }
                    }
                }
            } else {
                continue;
            }

            //------------
            // validator
            $validatorSet   = acmsUnserialize($data['validatorSet']);

            if ( is_array($validatorSet) ) {
                $valid          = $validatorSet['validator'];
                $validValue     = $validatorSet['validator-value'];
                $validMess      = $validatorSet['validator-message'];
                $validator      = array_combine($valid, $validValue);
                $validatorMess  = array_combine($valid, $validMess);
            } else {
                $valid          = array();
                $validValue     = array();
                $validMess      = array();
                $validator      = array();
                $validatorMess  = array();
            }

            $required   = false;
            foreach ( $validator as $key => $val ) {
                if ( empty($key) ) continue;
                if ( $key === 'converter' ) {
                    $Tpl->add(array('converter:loop', $type, 'column:loop'), array(
                        'vutid' => $utid,
                        'val'   => $val,
                    ));
                } else {
                    if ( $key === 'required' ) $required = true; 
                    $Tpl->add(array('validator:loop', $type, 'column:loop'), array(
                        'vutid' => $utid,
                        'valid' => $key,
                        'val'   => $val,
                    ));
                }
            }
            if ( $required ) $Tpl->add(array('required', $type, 'column:loop'));
            foreach ( $validatorMess as $key => $val ) {
                if ( empty($key) ) continue;
                $Tpl->add(array('validatorMessage:loop', $type, 'column:loop'), array(
                    'vutid'     => $utid,
                    'valid'     => $key,
                    'message'   => $val,
                ));
            }

            $Tpl->add(array($type, 'column:loop'), $vars);
            $Tpl->add('column:loop');
        }
        return true;
    }
}
