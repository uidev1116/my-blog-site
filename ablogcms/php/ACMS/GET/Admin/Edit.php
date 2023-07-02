<?php

class ACMS_GET_Admin_Edit extends ACMS_GET_Admin
{
    function edit(& $Tpl)
    {
        return true;
    }

    function auth()
    {
        if ( 1
            && 'user_edit' <> ADMIN
            && 'user_password' <> ADMIN
            && !sessionWithContribution()
        ) {
            return false;
        }

        if ( 1
            && 'top' <> ADMIN
            && 'user_edit' <> ADMIN
            && 'user_password' <> ADMIN
            && 'entry_index' <> ADMIN
            && 'entry_index' <> ADMIN
            && 'entry_editor' <> ADMIN
            && !sessionWithCompilation()
        ) {
            return false;
        }
        return true;
    }

    function get()
    {
        if ( !$this->auth() ) {
            return '';
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();
        $edit   = 'update';
        $edit_  = $this->Get->get('edit');
        if ( !empty($edit_) ) {
            $edit   = $edit_;
        }
        if (!$this->Post->isValidAll()) {
            $Tpl->add('msg#error');
        } else if ( $this->Post->isValidAll() && $this->Post->isExists('edit') ) {
            $edit = $this->Post->get('edit');
            $this->Post->set('notice_mess', 'show');
            $Tpl->add('msg#'.$edit);
            $Tpl->add('msg:other');
        } else if ( $this->Post->get('validate', false) ) {
            $Tpl->add('msg#error');
        } else if ( $this->Get->get('msg') === 'new' ) {
            $Tpl->add('msg#insert');
        }

        $this->edit = $edit;
        if ( !$this->edit($Tpl) ) return false;

        $vars   += $this->buildField($this->Post, $Tpl);
        $this->Post->reset(true);
        $this->Post->deleteField('edit');
        $vars   += $this->buildEdit($this->edit, $Tpl);

        $Tpl->add(null, $vars);

        return $Tpl->get();
    }

    function buildEdit($edit, & $Tpl)
    {
        $suffix = !(empty($edit) or is_bool($edit)) ? '#'.$edit : '';
        $Tpl->add('header'.$suffix);
        $Tpl->add('footer'.$suffix);
        $Tpl->add('headline'.$suffix);
        $Tpl->add('submit'.$suffix);
        $Tpl->add('submit2'.$suffix);
        $Tpl->add('takeover'.$suffix, array(
            'takeover' => acmsSerialize($this->Post)
        ));

        if ( !(empty($edit) or is_bool($edit)) ) {
            $Tpl->add('header:other');
            $Tpl->add('footer:other');
            $Tpl->add('headline:other');
            $Tpl->add('submit:other');
            $Tpl->add('submit2:other');
            $Tpl->add('takeover:other', array(
                'takeover' => acmsSerialize($this->Post)
            ));
        }

        return array('editStatus' => preg_replace('/^#/', '', $suffix));
    }

    function buildArgLabels(& $Field)
    {
        foreach ( array('bid', 'uid', 'cid', 'eid', 'session_uid') as $arg ) {
            $args       = $Field->get($arg);
            $args       = preg_split('/,/', preg_replace('/\s　/', '', $args));
            $argLabels  = array();

            switch ( $arg ) {
                case 'bid':
                    foreach ( $args as $val ) {
                        if ( !empty($val) ) {
                            $argLabels[] = array(
                                'label' => ACMS_RAM::blogName($val).'（bid:'.$val.'）',
                                'value' => $val,
                            );
                        }
                    }
                    break;
                case 'uid':
                    foreach ( $args as $val ) {
                        if ( !empty($val) ) {
                            $argLabels[] = array(
                                'label' => ACMS_RAM::userName($val).'（uid:'.$val.'）',
                                'value' => $val,
                            );
                        }
                    }
                    break;
                case 'session_uid':
                    foreach ( $args as $val ) {
                        if ( !empty($val) ) {
                            $argLabels[] = array(
                                'label' => ACMS_RAM::userName($val).'（uid:'.$val.'）',
                                'value' => $val,
                            );
                        }
                    }
                    break;
                case 'cid':
                    foreach ( $args as $val ) {
                        if ( !empty($val) ) {
                            $argLabels[] = array(
                                'label' => ACMS_RAM::categoryName($val).'（cid:'.$val.'）',
                                'value' => $val,
                            );
                        }
                    }
                    break;
                case 'eid':
                    foreach ( $args as $val ) {
                        if ( !empty($val) ) {
                            $argLabels[] = array(
                                'label' => ACMS_RAM::entryTitle($val).'（eid:'.$val.'）',
                                'value' => $val,
                            );
                        }
                    }
                    break;
                default:
                    break;
            }
            foreach ( $argLabels as $label ) {
                $Field->add($arg.'_arg_label', $label['label']);
                $Field->add($arg.'_arg_value', $label['value']);
            }
            $Field->add('@'.$arg.'_arg', $arg.'_arg_label');
            $Field->add('@'.$arg.'_arg', $arg.'_arg_value');
        }
    }
}
