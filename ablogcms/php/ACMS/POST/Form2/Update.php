<?php

class ACMS_POST_Form2_Update extends ACMS_POST_Entry
{
    protected function extractFormColumn()
    {
        $Column     = [];
        $overCount  = 0;

        $typeAry = $this->Post->getArray('type');
        if (empty($typeAry)) {
            return $Column;
        }
        foreach ($typeAry as $i => $type) {
            $id = $this->Post->get('id', 0, $i);

            // text, textarea
            if (in_array($type, ['text', 'textarea'], true)) {
                $data   = [
                    'label'     => $this->Post->get($type . '_label_' . $id),
                    'caption'   => $this->Post->get($type . '_caption_' . $id),
                ];
            // radio, select, checkbox
            } elseif (in_array($type, ['radio', 'select', 'checkbox'], true)) {
                $values = array_merge(array_diff($this->Post->getArray($type . '_value_' . $id), [""]));
                $data   = [
                    'label'             => $this->Post->get($type . '_label_' . $id),
                    'caption'           => $this->Post->get($type . '_caption_' . $id),
                    'values'            => acmsSerialize($values),
                ];
            } else {
                continue;
            }
            $baseCol = [
                'id'                => $id,
                'clid'              => $_POST['clid'][$i],
                'type'              => $type,
                'sort'              => @intval($_POST['sort'][$i]),
                'validator'         => $_POST['column_validator_' . $id],
                'validator-value'   => $_POST['column_validator-value_' . $id],
                'validator-message' => $_POST['column_validator-message_' . $id],
            ];

            $Column[]   = $data + $baseCol;
        }
        return $Column;
    }

    protected function saveFormColumn(&$Column, $eid, $bid)
    {
        $DB     = DB::singleton(dsn());
        $offset = 0;

        if (!empty($eid) && !empty($bid)) {
            $SQL    = SQL::newDelete('column');
            $SQL->addWhereOpr('column_entry_id', $eid);
            $SQL->addWhereOpr('column_blog_id', $bid);
            $SQL->addWhereOpr('column_attr', 'acms-form');
            $DB->query($SQL->get(dsn()), 'exec');
        }

        foreach ($Column as $data) {
            $id     = $data['id'];
            $type   = $data['type'];

            $valid  = [
                'validator'         => $data['validator'],
                'validator-value'   => $data['validator-value'],
                'validator-message' => $data['validator-message'],
            ];

            $row    = [
                'column_align'      => '',
                'column_attr'       => 'acms-form',
                'column_group'      => '',
                'column_type'       => $type,
                'column_field_1'    => $data['label'],
                'column_field_2'    => $data['caption'],
                'column_field_7'    => acmsSerialize($data['validator-message']),
                'column_field_8'    => acmsSerialize($valid),
            ];

            if (empty($data['label'])) {
                $offset++;
                continue;
            }

            //----------------
            // text, textarea
            if (in_array($type, ['text', 'textarea'], true)) {
            //-------------------------
            // radio, select, checkbox
            } elseif (in_array($type, ['radio', 'select', 'checkbox'], true)) {
                $row['column_field_6']  = $data['values'];
            } else {
                $offset++;
                continue;
            }

            if (!empty($data['clid'])) {
                $clid   = intval($data['clid']);
            } else {
                $clid   = $DB->query(SQL::nextval('column_id', dsn()), 'seq');
            }
            $sort   = intval($data['sort'] - $offset);

            $SQL    = SQL::newSelect('column');
            $SQL->setSelect('column_id');
            $SQL->addWhereOpr('column_sort', $sort);
            $SQL->addWhereOpr('column_entry_id', intval($eid));
            $SQL->addWhereOpr('column_blog_id', intval($bid));
            if ($DB->query($SQL->get(dsn()), 'one')) {
                $SQL    = SQL::newUpdate('column');
                $SQL->setUpdate('column_sort', SQL::newOpr('column_sort', 1, '+'));
                $SQL->addWhereOpr('column_sort', $sort, '>=');
                $SQL->addWhereOpr('column_entry_id', intval($eid));
                $SQL->addWhereOpr('column_blog_id', intval($bid));
                $DB->query($SQL->get(dsn()), 'exec');
            }

            $SQL    = SQL::newInsert('column');
            foreach ($row as $fd => $val) {
                $SQL->addInsert($fd, strval($val));
            }
            $SQL->addInsert('column_id', intval($clid));
            $SQL->addInsert('column_sort', intval($sort));
            $SQL->addInsert('column_entry_id', intval($eid));
            $SQL->addInsert('column_blog_id', intval($bid));
            $DB->query($SQL->get(dsn()), 'exec');
        }

        return true;
    }

    protected function update($oldField = null)
    {
        $DB     = DB::singleton(dsn());
        $Form   = $this->extract('form');
        $Form->setMethod('form_id', 'required');
        $Form->validate(new ACMS_Validator());

        $Column = $this->extractFormColumn();

        if (!$this->Post->isValidAll()) {
            $this->Post->set('step', 'reapply');
            $this->Post->set('action', 'update');
            Entry::setTempUnitData($Column);

            AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーの動的フォームを更新に失敗しました', [
                'Form' => $Form,
                'Column' => $Column,
            ]);
            return false;
        }
        //--------
        // column
        $this->saveFormColumn($Column, EID, BID);

        $SQL    = SQL::newUpdate('entry');
        $row    = [
            'entry_updated_datetime'    => date('Y-m-d H:i:s', REQUEST_TIME),
            'entry_form_id'             => $Form->get('form_id'),
            'entry_form_status'         => $Form->get('form_status'),
        ];
        foreach ($row as $key => $val) {
            $SQL->addUpdate($key, $val);
        }
        $SQL->addWhereOpr('entry_id', EID);
        $SQL->addWhereOpr('entry_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');
        ACMS_RAM::entry(EID, null);

        AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーの動的フォームを更新しました', [
            'eid' => EID,
            'Form' => $Form,
            'Column' => $Column,
        ]);

        return ['eid' => EID, 'cid' => CID];
    }

    public function post()
    {
        $updatedResponse = $this->update();

        $redirect   = $this->Post->get('redirect');
        $nextstep   = $this->Post->get('nextstep');

        setCookieDelFlag();

        if (is_array($updatedResponse)) {
            $this->redirect(acmsLink([
                'bid'   => BID,
                'cid'   => $updatedResponse['cid'],
                'eid'   => EID,
            ]));
        } else {
            return $this->Post;
        }
    }

    function isOperable()
    {
        if (!EID) {
            return false;
        }
        if (!IS_LICENSED) {
            return false;
        }
        if (!sessionWithCompilation()) {
            if (!sessionWithContribution()) {
                return false;
            }
            if (SUID <> ACMS_RAM::entryUser(EID)) {
                return false;
            }
        }

        return true;
    }
}
