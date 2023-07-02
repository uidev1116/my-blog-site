<?php

class ACMS_GET_Admin_Form_Edit extends ACMS_GET_Admin_Edit
{
    function auth()
    {
        if ( 0
            || ( !roleAvailableUser() && !sessionWithFormAdministration() )
            || ( roleAvailableUser() && !roleAuthorization('form_view', BID) && !roleAuthorization('form_edit', BID) )
        ) {
            return false;
        }
        return true;
    }

    function edit(& $Tpl)
    {
        $Form =& $this->Post->getChild('form');

        if ( empty($this->step) and ($fmid = intval($this->Get->get('fmid'))) ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('form');
            $SQL->addWhereOpr('form_id', $fmid);
            if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
                $Form->set('code', $row['form_code']);
                $Form->set('name', $row['form_name']);
                $Form->set('scope', $row['form_scope']);
                $Form->set('log', $row['form_log']);
                $Form->overload(unserialize($row['form_data']), true);
            }
        }

        $Mail   = $Form->getChild('mail');
        if ( !$Mail->isNull() ) {
            $vars   = array();
            foreach ( $Mail->listFields() as $fd ) {
                $vars[$fd]  = join(', ', $Mail->getArray($fd));
                if (preg_match('/^(AdminAttachment|template)/', $fd)) {
                    $vars[ $fd . ":checked#" . $Mail->get($fd) ]  = config('attr_checked');
                    $Tpl->add(array($fd . ":checked#" . $Mail->get($fd), 'mail'), NULL);
                }
            }
            $Tpl->add(array('mail'), $vars);
        } else {
            $vars   = array(
                'Charset'       => 'ISO-2022-JP',
                'CharsetHTML'   => 'UTF-8',
            );
            $Tpl->add(array('mail'), $vars);
        }
        $Option = $Form->getChild('option');

        if ( !$Option->isNull() ) {
            foreach ( $Option->getArray('field') as $i => $fd ) {

                if ( empty($fd) ) continue;
                if ( !$method = $Option->get('method', '', $i) ) continue;

                $value  = $Option->get('value', '', $i);
                $Tpl->add(array('method:touch#'.$method));
                $Tpl->add(array('option:loop'), array(
                    'field'     => $fd,
                    'value'     => $value,
                    'method:selected#'.$method  => config('attr_selected'),
                ));
            }
        }

        return true;
    }
}
