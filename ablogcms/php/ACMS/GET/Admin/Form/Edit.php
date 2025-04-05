<?php

class ACMS_GET_Admin_Form_Edit extends ACMS_GET_Admin_Edit
{
    public function auth()
    {
        if (
            0
            || ( !roleAvailableUser() && !sessionWithFormAdministration() )
            || ( roleAvailableUser() && !roleAuthorization('form_view', BID) && !roleAuthorization('form_edit', BID) )
        ) {
            return false;
        }
        return true;
    }

    public function edit(&$Tpl)
    {
        $Form =& $this->Post->getChild('form');

        $formId = intval($this->Get->get('fmid'));
        if ($formId > 0) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('form');
            $SQL->addWhereOpr('form_id', $formId);
            if ($row = $DB->query($SQL->get(dsn()), 'row')) {
                $formData = acmsDangerUnserialize($row['form_data']);
                $Form->set('code', $row['form_code']);
                $Form->set('name', $row['form_name']);
                $Form->set('scope', $row['form_scope']);
                $Form->set('log', $row['form_log']);
                $Form->overload($formData, true);
            }
        }

        $Mail   = $Form->getChild('mail');
        if (!$Mail->isNull()) {
            $vars   = [];
            foreach ($Mail->listFields() as $fd) {
                $vars[$fd]  = join(', ', $Mail->getArray($fd));
                if (preg_match('/^(AdminAttachment|template|FormSend|AdminFormSend)/', $fd)) {
                    $vars[ $fd . ":checked#" . $Mail->get($fd) ]  = config('attr_checked');
                    $Tpl->add([$fd . ":checked#" . $Mail->get($fd), 'mail'], null);
                }
            }
            $Tpl->add(['mail'], $vars);
        } else {
            $vars   = [
                'Charset'       => 'ISO-2022-JP',
                'CharsetHTML'   => 'UTF-8',
            ];
            $Tpl->add(['mail'], $vars);
        }
        $Option = $Form->getChild('option');

        if (!$Option->isNull()) {
            foreach ($Option->getArray('field') as $i => $fd) {
                if (empty($fd)) {
                    continue;
                }
                if (!$method = $Option->get('method', '', $i)) {
                    continue;
                }

                $value  = $Option->get('value', '', $i);
                $Tpl->add(['method:touch#' . $method]);
                $Tpl->add(['option:loop'], [
                    'field'     => $fd,
                    'value'     => $value,
                    'method:selected#' . $method  => config('attr_selected'),
                ]);
            }
        }

        return true;
    }
}
