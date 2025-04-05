<?php

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Entry;

class ACMS_GET_Admin_Entry_Edit extends ACMS_GET_Admin_Entry
{
    /**
     * @var array
     */
    public $fieldNames  =  [];

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
        if (!sessionWithContribution(BID)) {
            return '';
        }
        if ('entry-edit' <> ADMIN && 'entry_editor' <> ADMIN && 'entry-field' <> ADMIN) {
            return '';
        }
        if (!defined('IS_EDITING_ENTRY')) {
            define('IS_EDITING_ENTRY', true);
        }
        /** @var \Acms\Services\Unit\Repository $unitService */
        $unitService = Application::make('unit-repository');
        /** @var \Acms\Services\Unit\Rendering\Edit $unitRenderingService */
        $unitRenderingService = Application::make('unit-rendering-edit');

        $CustomFieldCollection = [];
        $units = Entry::getTempUnitData();
        $vars = [];

        if (
            !$this->Post->isNull() &&
            (!$this->Post->get('backend') || !$this->Post->isValidAll()) &&
            is_array($units)
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

            // サブカテゴリーの選択肢を保持する
            /** @var int[] $subCategoryIds */
            $subCategoryIds = array_map('intval', array_map('trim', explode(',', $Entry->get('sub_category_id'))));
            if (count($subCategoryIds) > 0) {
                $subCategories = $this->findCategories($subCategoryIds);
                $entrySubCategoryIds = array_column($subCategories, 'id');
                $entrySubCategoryLabels = array_column($subCategories, 'label');
                $Entry->setField('sub_category_id', implode(',', $entrySubCategoryIds));
                $Entry->setField('sub_category_label', implode(',', $entrySubCategoryLabels));
            }
        } else {
            $Entry  = new Field_Validation();
            $Field  = new Field_Validation();
            $Geo    = new Field_Validation();

            $DB = DB::singleton(dsn());

            $units = [];
            if (EID) {
                // 更新
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
                $SQL->addOrder('tag_sort', 'ASC');
                $q  = $SQL->get(dsn());
                if ($DB->query($q, 'fetch') and ($row = $DB->fetch($q))) {
                    do {
                        $tag    .= !empty($tag) ? ', ' : '';
                        $tag    .= $row['tag_name'];
                    } while ($row = $DB->fetch($q));
                    $Entry->setField('tag', $tag);
                }

                /**
                 * ユニット
                 * @var \Acms\Services\Unit\Contracts\Model[] $units
                 */
                $units = $unitService->loadUnits(EID, $RVID_);
                if (!is_ajax() && $units) {
                    // ユニット一時IDとソート番号を振り直し
                    array_walk($units, function (&$unit, $i) {
                        $unit->setTempId(uniqueString());
                        $unit->setSort($i + 1);
                    });
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
                // 新規エントリー
                $step = 'apply';
                $action = 'insert';

                /** @var \Acms\Services\Unit\Repository $unitService */
                $unitService = Application::make('unit-repository');
                /** @var \Acms\Services\Unit\Rendering\Edit $unitRenderingService */
                $unitRenderingService = Application::make('unit-rendering-edit');
                $units = $unitService->loadDefaultUnit();

                $vars['status:selected#' . config('initial_entry_status', 'draft')] = config('attr_selected');
                $vars['indexing:checked#' . config('entry_edit_indexing_default', 'on')] = config('attr_checked');
                $vars['members_only:checked#' . config('entry_edit_members_only_default', 'off')] = config('attr_checked'); // phpcs:ignore
            }
        }

        $rootBlock  = 'step#' . $step;
        $pattern    = '/<!--[\t 　]*BEGIN[\t 　]+' . $rootBlock . '[^>]*?-->(.*)<!--[\t 　]*END[\t 　]+' . $rootBlock . '[^>]*?-->/s';
        if (preg_match($pattern, $this->tpl, $matches)) {
            $this->tpl = $matches[0];
        }
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        //--------
        // units
        $primaryImageUnitId = $Entry->get('primary_image') ? (int) $Entry->get('primary_image') : null;
        $unitRenderingService->render($units, $Tpl, $primaryImageUnitId, [$rootBlock]);

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
                Tpl::buildRelatedEntries($Tpl, $relatedEids, ['related_group:loop', $rootBlock], $this->start, $this->end, 'other_related:loop');
            }
            $Tpl->add(['related_group:loop', $rootBlock], [
                'related_label' => $label,
                'related_type' => $type,
                'related_module_id' => $moduleId,
                'related_ctx' => $ctx,
                'related_max_item' => $maxItem,
            ]);
        }

        //---------------
        // summary range
        $summaryRange   = $Entry->get('summary_range');
        $columnAmount   = count($units);
        if ($columnAmount < $summaryRange) {
            $summaryRange = $columnAmount;
        }
        for ($i = 1; $i <= $columnAmount; $i++) {
            $_vars  = ['value' => $i];
            if ($summaryRange == $i) {
                $_vars['selected']   = config('attr_selected');
            }
            $Tpl->add(['range:loop', $rootBlock], $_vars);
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
                $Tpl->add(['action#categoryInsert', $rootBlock]);
            }

            $Tpl->add(['action#confirm', $rootBlock]);
            $Tpl->add(['action#' . $action, $rootBlock]);

            if ('entry-edit' == ADMIN) {
                $Tpl->add(['view#frontend', $rootBlock]);
            } elseif ('entry_editor' == ADMIN) {
                $Tpl->add(['view#backend', $rootBlock]);
                $Tpl->add(['backend', $rootBlock]);
            } elseif ('entry-field' == ADMIN && $this->Post->get('backend')) {
                $Tpl->add(['message', $rootBlock]);
            }
        }
        if ('update' == $action) {
            $Tpl->add(['action#delete', $rootBlock]);
        }

        $Tpl->add($rootBlock, $vars);
        return $Tpl->get();
    }

    /**
     * @param int[] $categoryIds
     * @return array{
     *  id: int,
     *  label: string
     * }[]
     */
    protected function findCategories(array $categoryIds): array
    {
        $categories = [];
        $sql = SQL::newSelect('category');
        $sql->addWhereIn('category_id', $categoryIds);
        $q = $sql->get(dsn());
        if (DB::query($q, 'fetch')) {
            while ($row = DB::fetch($q)) {
                $categories[] = [
                    'id' => (int)$row['category_id'],
                    'label' => (string)$row['category_name']
                ];
            }
        }
        return $categories;
    }
}
