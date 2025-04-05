<?php

class ACMS_GET_Admin_Form2_Edit extends ACMS_GET_Admin_Entry
{
    public function get()
    {
        if (!sessionWithContribution()) {
            return '';
        }
        if ('form2-edit' !== ADMIN) {
            return '';
        }
        if (!EID) {
            return false;
        }
        if (config('form_edit_action_direct') !== 'on') {
            return false;
        }

        $DB         = DB::singleton(dsn());
        $Tpl        = new Template($this->tpl, new ACMS_Corrector());

        if (!$this->Post->isNull()) {
            $step       = $this->Post->get('step');
            $action     = $this->Post->get('action');
            $formId     = $this->Post->get('form_id');
            $formStatus = $this->Post->get('form_status');
            $Form       =& $this->Post->getChild('form');
            $Column     = Entry::getTempUnitData();
        } else {
            $Form       = new Field();
            $Field      = new Field();
            $Column     = [];
            $step       = 'reapply';
            $action     = 'update';

            $row        = ACMS_RAM::entry(EID);
            $formId     = $row['entry_form_id'];
            $formStatus = $row['entry_form_status'];

            //--------
            // column
            if ($Column = loadFormUnit(EID)) {
                $cnt    = count($Column);
                for ($i = 0; $i < $cnt; $i++) {
                    $Column[$i]['id']   = uniqueString();
                    $Column[$i]['sort'] = $i + 1;
                }
            }
        }

        $vars   = [];
        $rootBlock  = 'step#' . $step;

        //----------
        // form set
        $SQL = SQL::newSelect('form');
        $Where  = SQL::newWhere();
        $Where->addWhereOpr('form_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('form_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);
        $SQL->setOrder('form_current_serial');

        if ($all = $DB->query($SQL->get(dsn()), 'all')) {
            foreach ($all as $val) {
                if ($val['form_id'] === $formId) {
                    $val['selected'] = config('attr_selected');
                }
                $Tpl->add(['form:loop', $rootBlock], $val);
            }
        }

        //--------
        // column
        foreach (configArray('column_form_add_type') as $i => $type) {
            $aryTypeLabel[$type]    = config('column_form_add_type_label', '', $i);
        }

        if ($cnt = count($Column)) {
            foreach ($Column as $data) {
                $id     = $data['id'];
                $clid   = intval(ite($data, 'clid'));
                $type   = $data['type'];
                $sort   = $data['sort'];

                        //--------------
                        // build column
                if (!$this->buildFormColumn($data, $Tpl, $rootBlock)) {
                    continue;
                }

                        //------
                        // sort
                for ($i = 1; $i <= $cnt; $i++) {
                    $_vars  = [
                        'value' => $i,
                        'label' => $i,
                    ];
                    if ($sort == $i) {
                        $_vars['selected']   = config('attr_selected');
                    }
                    $Tpl->add(['sort:loop', $rootBlock], $_vars);
                }

                $Tpl->add(['column:loop', $rootBlock], [
                    'uniqid'    => $id,
                    'clid'      => $clid,
                    'cltype'    => $type,
                    'clname'    => ite($aryTypeLabel, $type),
                ]);
            }
        } else {
            //-----------
            // [CMS-608]
            $Tpl->add(['adminEntryColumn', $rootBlock]);
        }

        //--------------
        // Form
        $vars   += $this->buildField($Form, $Tpl, $rootBlock);

        //--------
        // action
        if (IS_LICENSED) {
            $Tpl->add(['action#confirm', $rootBlock]);
            $Tpl->add(['action#' . $action, $rootBlock]);
        }
        if ('update' == $action) {
            $Tpl->add(['action#delete', $rootBlock]);
        }

        //--------
        // status
        if (!empty($formStatus)) {
            $vars['form_status:selected#' . $formStatus] = config('attr_selected');
        }

        $Tpl->add($rootBlock, $vars);

        return $Tpl->get();
    }
}
