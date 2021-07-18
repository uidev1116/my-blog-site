<?php

class ACMS_GET_Shop_Address_Edit extends ACMS_GET_Shop
{
    function get()
    {
        $Address    =& $this->Post->getChild('address');
        $Field      =& $this->Post->getChild('field');
        $Tpl        = new Template($this->tpl, new ACMS_Corrector());

        $step       = $this->Post->get('step', 'detail');
        $edit       = empty($_GET['aid']) ? 'insert' : 'update';

        if ( $edit == 'update' && $this->Post->isNull() ) {
            $Address->overload($this->loadAddress($_GET['aid']));
			
			$Field->overload( loadUserField( SUID ) );
			
        } elseif ( $edit == 'insert' && $this->Post->isNull() ) {
            $step = 'reapply';
        } elseif ( !$this->Post->isNull() && $Address->isNull() ) {
            $Address = acmsUnserialize($this->Post->get('address:takeover'));
            $Field = acmsUnserialize($this->Post->get('field:takeover'));
        }
		
        $root       = empty($step) ? 'step' : 'step#'.$step;
        $vars = $this->buildField($Address, $Tpl, $root, 'address');
        $vars += $this->buildField($Field, $Tpl, $root, 'field');
        $vars += $this->buildEdit($edit, $Tpl, $root);
		
        $Tpl->add($root, $vars);
		
        return $Tpl->get();
    }

    function loadAddress($aid)
    {
        $Address   = new Field_Validation();
        if ( !empty($aid) ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('shop_address');
            $SQL->addWhereOpr('address_id', intval($aid));
            $SQL->addWhereOpr('address_user_id', SUID);
            if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
                foreach ( $row as $key => $val ) {
                    $Address->set(substr($key, strlen('address_')), $val);
                }
            }
        }
        return $Address;
    }

    function buildEdit($edit, & $Tpl, $block=array())
    {
        $suffix = !(empty($edit) or is_bool($edit)) ? '#'.$edit : '';
        $Tpl->add(array('header'.$suffix, $block));
        $Tpl->add(array('footer'.$suffix, $block));
        $Tpl->add(array('msg'.$suffix, $block));
        $Tpl->add(array('headline'.$suffix, $block));
        $Tpl->add(array('submit'.$suffix, $block));
        $Tpl->add(array('takeover'.$suffix, $block), array(
            'takeover' => acmsSerialize($this->Post)
        ));

        if ( !(empty($edit) or is_bool($edit)) ) {
            $Tpl->add(array('header:other', $block));
            $Tpl->add(array('footer:other', $block));
            $Tpl->add(array('msg:other', $block));
            $Tpl->add(array('headline:other', $block));
            $Tpl->add(array('submit:other', $block));
            $Tpl->add(array('takeover:other'.$suffix, $block), array(
                'takeover' => acmsSerialize($this->Post)
            ));
        }

        return array();
    }

}