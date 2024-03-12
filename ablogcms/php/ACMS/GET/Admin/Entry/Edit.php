<?php

class ACMS_GET_Admin_Entry_Edit extends ACMS_GET_Admin_Entry
{
    /**
     * @var array
     */
    public $fieldNames  = array ();

    /**
     * @see ACMS_User_GET_EntryExtendSample_Edit
     *
     * @param string $fieldName
     * @param int    $eid
     * @return Field
     */
    function loadCustomField($fieldName, $eid)
    {
        $Field = new Field_Validation();
        return $Field;
    }

    function get()
    {
        if (!sessionWithContribution(BID, false)) {
            return false;
        }
        if ('entry-edit' <> ADMIN && 'entry_editor' <> ADMIN && 'entry-field' <> ADMIN) {
            return false;
        }

        $CustomFieldCollection = array();
        $Column = acmsUnserialize($this->Post->get('column'));
        $vars = array();

        if (
            1
            && !$this->Post->isNull()
            && ( !$this->Post->get('backend') || !$this->Post->isValidAll() )
            && is_array($Column)
        ) {
            $step   = $this->Post->get('step');
            $action = $this->Post->get('action');
            $Entry  =& $this->Post->getChild('entry');
            $Field  =& $this->Post->getChild('field');
            $Geo    =& $this->Post->getChild('geometry');
            $relateds = $Entry->getArray('related');
            $relatedTypes = $Entry->getArray('related_type');
            $Entry->deleteField('related');
            foreach ($relateds as $i => $related) {
                $type = $relatedTypes[$i];
                if ($type) {
                    $Entry->addField('related_' . $type, $related);
                } else {
                    $Entry->addField('related', $related);
                }
            }
            $Column = alignColumn($Column);
        } else {
            $Entry  = new Field_Validation();
            $Field  = new Field_Validation();
            $Geo    = new Field_Validation();

            $DB = DB::singleton(dsn());

            $Column = array();
            if (EID) {
                $step   = 'reapply';
                $action = 'update';

                if (RVID) {
                    $SQL    = SQL::newSelect('entry_rev');
                    $SQL->addWhereOpr('entry_id', EID);
                    $SQL->addWhereOpr('entry_blog_id', BID);
                    $SQL->addWhereOpr('entry_rev_id', RVID);
                    $row    = $DB->query($SQL->get(dsn()), 'row');
                } else {
                    $row    = ACMS_RAM::entry(EID);
                }
                if (empty($row)) {
                    return;
                }
                $RVID_  = RVID;
                if (!RVID && $row['entry_approval'] === 'pre_approval') {
                    $RVID_  = 1;
                }

                //--------------
                // custom field
                $Field  = loadEntryField(EID, $RVID_, true);
                foreach ($this->fieldNames as $fieldName) {
                    $CustomFieldCollection[$fieldName]   = $this->loadCustomField($fieldName, EID);
                }

                $Entry->setField('status', $row['entry_status']);
                $Entry->setField('title', $row['entry_title']);
                $Entry->setField('code', $row['entry_code']);
                $Entry->setField('link', $row['entry_link']);
                $Entry->setField('indexing', $row['entry_indexing']);
                $Entry->setField('members_only', $row['entry_members_only']);
                $Entry->setField('summary_range', $row['entry_summary_range']);
                $Entry->setField('category_id', $row['entry_category_id']);
                $Entry->setField('primary_image', $row['entry_primary_image']);

                list($date, $time)  = explode(' ', $row['entry_datetime']);
                $Entry->setField('date', $date);
                $Entry->setField('time', $time);

                list($date, $time)  = explode(' ', $row['entry_start_datetime']);
                $Entry->setField('start_date', $date);
                $Entry->setField('start_time', $time);

                list($date, $time)  = explode(' ', $row['entry_end_datetime']);
                $Entry->setField('end_date', $date);
                $Entry->setField('end_time', $time);

                //-----
                // tag
                $tag    = '';
                if (RVID) {
                    $SQL    = SQL::newSelect('tag_rev');
                    $SQL->addWhereOpr('tag_rev_id', RVID);
                } else {
                    $SQL    = SQL::newSelect('tag');
                }
                $SQL->setSelect('tag_name');
                $SQL->addWhereOpr('tag_entry_id', EID);
                $q  = $SQL->get(dsn());
                if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
                    do {
                        $tag    .= !empty($tag) ? ', ' : '';
                        $tag    .= $row['tag_name'];
                    } while ($row = $DB->fetch($q));
                    $Entry->setField('tag', $tag);
                }

                //--------
                // column
                if (
                    1
                    && !is_ajax()
                    && $Column = loadColumn(EID, null, $RVID_)
                ) {
                    $cnt    = count($Column);
                    for ($i = 0; $i < $cnt; $i++) {
                        $Column[$i]['id']   = uniqueString();
                        $Column[$i]['sort'] = $i + 1;
                    }
                }

                //--------------
                // sub category
                if ($subCategories = loadSubCategories(EID, $RVID_)) {
                    $subCategoryId = $subCategories['id'];
                    $subCategoryLabel = $subCategories['label'];
                    $Entry->addField('sub_category_id', implode(',', $subCategoryId));
                    $Entry->addField('sub_category_label', implode(',', $subCategoryLabel));
                }

                //---------------
                // related entry
                if ($relatedEids = loadRelatedEntries(EID, $RVID_)) {
                    foreach ($relatedEids as $reid) {
                        $Entry->addField('related', $reid);
                    }
                }
                //--------------
                // related entry group
                foreach (configArray('related_entry_type') as $type) {
                    $relatedEids = loadRelatedEntries(EID, $RVID_, $type);
                    foreach ($relatedEids as $reid) {
                        $Entry->addField('related_' . $type, $reid);
                    }
                }

                //----------
                // geometry
                $Geo = loadGeometry('eid', EID, $RVID_);
            } else {
                $step   = 'apply';
                $action = 'insert';
                $aryType    = configArray('column_def_insert_type');
                $Column     = array();
                $vars['status:selected#' . config('initial_entry_status', 'draft')] = config('attr_selected');
                $vars['indexing:checked#' . config('entry_edit_indexing_default', 'on')] = config('attr_checked');
                $vars['members_only:checked#' . config('entry_edit_members_only_default', 'off')] = config('attr_checked'); // phpcs:ignore
                foreach ($aryType as $i => $type) {
                    if (!$data = Tpl::getAdminColumnDefinition('insert', $type, $i)) {
                        continue;
                    }
                    $Column[]   = $data + array(
                        'id'    => uniqueString(),
                        'type'  => $type,
                        'sort'  => $i + 1,
                        'align' => config('column_def_insert_align', 'auto', $i),
                        'group' => config('column_def_insert_group', '', $i),
                        'class' => config('column_def_insert_class', '', $i),
                        'attr'  => config('column_def_insert_class', '', $i),
                        'size'  => config('column_def_insert_size', '', $i),
                        'edit'  => config('column_def_insert_edit', '', $i),
                    );
                }
            }
        }

        $rootBlock  = 'step#' . $step;
        $pattern    = '/<!--[\t 　]*BEGIN[\t 　]+' . $rootBlock . '[^>]*?-->(.*)<!--[\t 　]*END[\t 　]+' . $rootBlock . '[^>]*?-->/s';
        if (preg_match($pattern, $this->tpl, $matches)) {
            $this->tpl = $matches[0];
        }
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        //--------
        // column
        $aryTypeLabel    = array();
        foreach (configArray('column_add_type') as $i => $type) {
            $aryTypeLabel[$type]    = config('column_add_type_label', '', $i);
        }

        if ($cnt = count($Column)) {
            $mediaData = Media::mediaEagerLoadFromUnit($Column);
            foreach ($Column as $data) {
                $id     = $data['id'];
                $clid   = intval(ite($data, 'clid'));
                $type   = $data['type'];
                $align  = $data['align'];
                $group  = $data['group'];
                $attr   = $data['attr'];
                $sort   = $data['sort'];

                // 特定指定子を含むユニットタイプ
                $actualType = $type;
                // 特定指定子を除外した、一般名のユニット種別
                $type = detectUnitTypeSpecifier($type);

                $data['primaryImage']   = $Entry->get('primary_image');

                //--------------
                // build column
                if (!$this->buildColumn($data, $Tpl, $rootBlock, $mediaData)) {
                    continue;
                }

                //------
                // sort
                for ($i = 1; $i <= $cnt; $i++) {
                    $_vars  = array(
                        'value' => $i,
                        'label' => $i,
                    );
                    if ($sort == $i) {
                        $_vars['selected']   = config('attr_selected');
                    }
                    $Tpl->add(array('sort:loop', $rootBlock), $_vars);
                }

                //-------
                // align
                if (in_array($type, array('text', 'custom', 'module', 'table'))) {
                    $Tpl->add(array('align#liquid', $rootBlock), array(
                        'align:selected#' . $align => config('attr_selected')
                    ));
                } else {
                    $Tpl->add(array('align#solid', $rootBlock), array(
                        'align:selected#' . $align => config('attr_selected')
                    ));
                }

                //-------
                // group
                if ('on' === config('unit_group')) {
                    $labels  = configArray('unit_group_label');
                    foreach ($labels as $i => $label) {
                        $class = config('unit_group_class', '', $i);
                        $Tpl->add(array('group:loop', $rootBlock), array(
                             'value' => $class,
                             'label' => $label,
                             'selected' => ($class === $group) ? config('attr_selected') : '',
                        ));
                    }
                }

                //------
                // attr
                if ($aryAttr = configArray('column_' . $type . '_attr')) {
                    foreach ($aryAttr as $i => $_attr) {
                        $label  = config('column_' . $type . '_attr_label', '', $i);
                        $_vars  = array(
                            'value' => $_attr,
                            'label' => $label,
                        );
                        if ($attr == $_attr) {
                            $_vars['selected'] = config('attr_selected');
                        }
                        $Tpl->add(array('clattr:loop', $rootBlock), $_vars);
                    }
                } else {
                    $Tpl->add(array('clattr#none', $rootBlock));
                }

                $Tpl->add(array('column:loop', $rootBlock), array(
                    'uniqid'    => $id,
                    'clid'      => $clid,
                    'cltype'    => $actualType,
                    'clattr'    => $attr,
                    'clname'    => ite($aryTypeLabel, $actualType),
                ));
            }
        } else {
            $Tpl->add(['adminEntryColumn', $rootBlock]);
        }

        //---------------
        // related entry
        $vars['related_entry_first_label'] = config('related_entry_first_label');
        $vars['related_entry_first_module_id'] = config('related_entry_first_module_id');
        $vars['related_entry_first_ctx'] = config('related_entry_first_ctx');
        $vars['related_entry_first_max_item'] = config('related_entry_first_max_item');
        if ($relatedEids = $Entry->getArray('related')) {
            $Entry->delete('related');
            Tpl::buildRelatedEntries($Tpl, $relatedEids, $rootBlock, $this->start, $this->end);
        }
        foreach (configArray('related_entry_type') as $i => $type) {
            if (empty($type)) {
                continue;
            }
            $relatedEids = $Entry->getArray('related_' . $type);
            $label = config('related_entry_label', '', $i);
            $moduleId = config('related_entry_module_id', '', $i);
            $ctx = config('related_entry_ctx', '', $i);
            $maxItem = config('related_entry_max_item', '', $i);
            if (!!$relatedEids) {
                $Entry->delete('related_' . $type);
                Tpl::buildRelatedEntries($Tpl, $relatedEids, array('related_group:loop', $rootBlock), $this->start, $this->end, 'other_related:loop');
            }
            $Tpl->add(array('related_group:loop', $rootBlock), array(
                'related_label' => $label,
                'related_type' => $type,
                'related_module_id' => $moduleId,
                'related_ctx' => $ctx,
                'related_max_item' => $maxItem,
            ));
        }

        //---------------
        // summary range
        $summaryRange   = $Entry->get('summary_range');
        $columnAmount   = count($Column);
        if ($columnAmount < $summaryRange) {
            $summaryRange = $columnAmount;
        }
        for ($i = 1; $i <= $columnAmount; $i++) {
            $_vars  = array('value' => $i);
            if ($summaryRange == $i) {
                $_vars['selected']   = config('attr_selected');
            }
            $Tpl->add(array('range:loop', $rootBlock), $_vars);
        }
        if ('0' === $summaryRange) {
            $vars['range:selected#none']    = config('attr_selected');
        } elseif (empty($summaryRange)) {
            $vars['range:selected#all']     = config('attr_selected');
        }
        $vars['summaryRange'] = $summaryRange;

        //----------
        // next eid
        if ($action == 'insert') {
            $DB = DB::singleton(dsn());
            $vars['next_eid'] = intval($DB->query(SQL::currval('entry_id', dsn()), 'one')) + 1;
        }

        //-------------------------
        // entry , field, geometry
        $vars   += $this->buildField($Entry, $Tpl, $rootBlock, 'entry');
        $vars   += $this->buildField($Field, $Tpl, $rootBlock, 'field');
        $vars   += $this->buildField($Geo, $Tpl, $rootBlock, 'geometry');
        $vars['column:takeover']  = base64_encode(gzdeflate(serialize($Column)));

        //--------------
        // custom field
        foreach ($CustomFieldCollection as $fieldName => $customField) {
            $vars   += $this->buildField($customField, $Tpl, $rootBlock, $fieldName);
        }

        //--------
        // action
        if (IS_LICENSED) {
            if (
                0
                || ( !roleAvailableUser() && sessionWithCompilation() )
                || ( roleAvailableUser() && roleAuthorization('category_create', BID) )
            ) {
                $Tpl->add(array('action#categoryInsert', $rootBlock));
            }

            $Tpl->add(array('action#confirm', $rootBlock));
            $Tpl->add(array('action#' . $action, $rootBlock));

            if ('entry-edit' == ADMIN) {
                $Tpl->add(array('view#frontend', $rootBlock));
            } elseif ('entry_editor' == ADMIN) {
                $Tpl->add(array('view#backend', $rootBlock));
                $Tpl->add(array('backend', $rootBlock));
            } elseif ('entry-field' == ADMIN && $this->Post->get('backend')) {
                $Tpl->add(array('message', $rootBlock));
            }
        }
        if ('update' == $action) {
            $Tpl->add(array('action#delete', $rootBlock));
        }

        $Tpl->add($rootBlock, $vars);
        return $Tpl->get();
    }
}
