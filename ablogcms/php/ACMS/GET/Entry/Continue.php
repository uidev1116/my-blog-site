<?php

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Database as DB;
use Acms\Services\Facades\Template as TemplateHelper;

class ACMS_GET_Entry_Continue extends ACMS_GET_Entry
{
    public $_axis = [
        'bid' => 'descendant-or-self',
        'cid' => 'descendant-or-self',
    ];

    public $_scope = [
        'uid' => 'global',
        'cid' => 'global',
        'eid' => 'global',
        'keyword' => 'global',
        'tag' => 'global',
        'field' => 'global',
        'date' => 'global',
        'start' => 'global',
        'end' => 'global',
        'page' => 'global',
    ];

    public function get()
    {
        $DB = DB::singleton(dsn());
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        TemplateHelper::buildModuleField($Tpl);

        $SQL = SQL::newSelect('entry');
        $SQL->addWhereOpr('entry_id', $this->eid);

        $q = $SQL->get(dsn());
        if (!$row = $DB->query($q, 'row')) {
            $Tpl->add('notFound');
            return $Tpl->get();
        }
        if (!IS_LICENSED) {
            $row['entry_title'] = '[test]' . $row['entry_title'];
        }

        $bid = $row['entry_blog_id'];
        $uid = $row['entry_user_id'];
        $cid = $row['entry_category_id'];
        $eid = $row['entry_id'];
        $link = $row['entry_link'];
        $datetime = $row['entry_datetime'];
        $inheritUrl = acmsLink([
            'eid' => $eid,
        ]);

        $vars = [];

        /**  @var \Acms\Services\Unit\Repository $unitService */
        $unitService = Application::make('unit-repository');
        /** @var \Acms\Services\Unit\Rendering\Front $unitRenderingService */
        $unitRenderingService = Application::make('unit-rendering-front');

        //-------
        // unit
        if ($units = $unitService->loadUnits($eid)) {
            $displayUnits = array_slice($units, (int) ($row['entry_summary_range'] ?? 0));
            if (!empty($displayUnits)) {
                $unitRenderingService->render($displayUnits, $Tpl, $eid);
            }
        }

        //-------
        // field
        if ('on' == config('entry_continue_field')) {
            $vars += $this->buildField(loadEntryField($this->eid), $Tpl, null, 'entry');
        }

        $vars += [
            'status' => $row['entry_status'],
            'url' => !empty($link) ? $link : $inheritUrl,
            'title' => addPrefixEntryTitle(
                $row['entry_title'],
                $row['entry_status'],
                $row['entry_start_datetime'],
                $row['entry_end_datetime'],
                $row['entry_approval']
            ),
            'bid' => $bid,
            'cid' => $cid,
            'eid' => $eid,
        ];

        //------
        // date
        $vars += $this->buildDate($row['entry_datetime'], $Tpl, null);
        $vars += $this->buildDate($row['entry_updated_datetime'], $Tpl, null, 'udate#');
        $vars += $this->buildDate($row['entry_posted_datetime'], $Tpl, null, 'pdate#');

        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
