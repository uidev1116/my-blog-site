<?php

use Acms\Services\Facades\Template as TemplateHelper;
use Acms\Services\Facades\Database;
use Acms\Services\Facades\Media;
use Acms\Services\Facades\Application;

class ACMS_GET_Unit_List extends ACMS_GET_Entry_Summary
{
    public $_axis = [
        'bid' => 'descendant-or-self',
        'cid' => 'descendant-or-self',
    ];

    public $_scope = [
        'cid' => 'global',
        'eid' => 'global',
        'start' => 'global',
        'end' => 'global',
    ];

    /**
     * コンフィグの取得
     *
     * @return array
     */
    function initVars()
    {
        return [
            'order' => [config('column_list_order')],
            'limit' => (int) config('column_list_limit'),
            'offset' => 0,
            'pagerOn' => 'on',
            'pagerDelta' => config('column_list_pager_delta'),
            'pagerCurAttr' => config('column_list_pager_cur_attr'),
        ];
    }


    function get()
    {
        if (!$this->setConfig()) {
            return '';
        }
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        TemplateHelper::buildModuleField($tpl);

        $vars = [];
        $sql = $this->buildQuery();
        $unitData = Database::query($sql->get(dsn()), 'all');
        if (count($unitData) > $this->config['limit']) {
            array_pop($unitData);
        }
        if (count($unitData) > 0) {
            $vars += $this->buildUnitTemplate($tpl, $unitData);
            $vars += $this->buildFullspecPager($tpl);
        }
        $tpl->add(null, $vars);

        return $tpl->get();
    }

    protected function buildUnitTemplate(Template $tpl, array $unitData): array
    {
        /** @var \Acms\Services\Unit\Repository $unitRepository */
        $unitRepository = Application::make('unit-repository');
        /** @var \Acms\Services\Unit\Contracts\Model[] $units */
        $units = $unitRepository->loadModels($unitData);
        $mediaEagerLoading = Media::mediaEagerLoadFromUnit($units);
        $vars = [];

        foreach ($unitData as $row) {
            $model = $unitRepository->loadModel($row);
            if (empty($model)) {
                continue;
            }
            $model->setEagerLoadedMedia($mediaEagerLoading);

            $eid = (int) $row['entry_id'];
            $cid = (int) $row['category_id'];
            $bid = (int) $row['blog_id'];
            $uid = (int) $row['entry_user_id'];

            if ($model instanceof \Acms\Services\Unit\Contracts\UnitListModule) {
                $row += $model->renderUnitListModule($tpl);
            }
            $row['entry_url'] = acmsLink([
                'bid' => $bid,
                'eid' => $eid,
            ]);
            if (!empty($cid)) {
                $row['category_url'] = acmsLink([
                    'bid' => $bid,
                    'cid' => $cid,
                ]);
            } else {
                unset($row['category_name']);
            }
            $row['blog_url'] = acmsLink([
                'bid' => $bid,
            ]);

            $tmp = [];
            foreach ($row as $key => $val) {
                if (empty($val)) {
                    unset($row[$key]);
                }
                $tmp[preg_replace('/column/', 'unit', $key)] = $val;
            }
            $row = $tmp;

            $row['unit:loop.class'] = config('column_list_loop_class');

            //-------------
            // entry field
            if (config('column_list_entry_on') === 'on') {
                if (config('column_list_entry_field') === 'on') {
                    $Field = loadEntryField($eid);
                } else {
                    $Field = new Field();
                }
                $Field->setField('fieldEntryTitle', ACMS_RAM::entryTitle($eid));
                $Field->setField('fieldEntryCode', ACMS_RAM::entryCode($eid));
                $Field->setField('fieldEntryDatetime', ACMS_RAM::entryDatetime($eid));

                $tpl->add(['entryField', 'unit:loop'], TemplateHelper::buildField($Field, $tpl, 'unit:loop'));
            }

            //-------------
            // user field
            if (config('column_list_user_on') === 'on') {
                if (config('column_list_user_field_on') === 'on') {
                    $Field = loadUserField($uid);
                } else {
                    $Field = new Field();
                }
                $Field->setField('fieldUserName', ACMS_RAM::userName($uid));
                $Field->setField('fieldUserCode', ACMS_RAM::userCode($uid));
                $Field->setField('fieldUserStatus', ACMS_RAM::userStatus($uid));
                $Field->setField('fieldUserMail', ACMS_RAM::userMail($uid));
                $Field->setField('fieldUserMailMobile', ACMS_RAM::userMailMobile($uid));
                $Field->setField('fieldUserUrl', ACMS_RAM::userUrl($uid));
                $Field->setField('fieldUserIcon', loadUserIcon($uid));
                if ($large = loadUserLargeIcon($uid)) {
                    $Field->setField('fieldUserLargeIcon', $large);
                }
                if ($orig = loadUserOriginalIcon($uid)) {
                    $Field->setField('fieldUserOrigIcon', $orig);
                }
                $tpl->add(['userField', 'unit:loop'], TemplateHelper::buildField($Field, $tpl, 'unit:loop'));
            }

            //------------
            // blog field
            if (config('column_list_blog_on') === 'on') {
                if (config('column_list_blog_field_on') === 'on') {
                    $Field = loadBlogField($bid);
                } else {
                    $Field = new Field();
                }
                $Field->setField('fieldBlogName', ACMS_RAM::blogName($bid));
                $Field->setField('fieldBlogCode', ACMS_RAM::blogCode($bid));
                $Field->setField('fieldBlogUrl', acmsLink(['bid' => $bid, '_protocol' => 'http'], false));

                $tpl->add(['blogField', 'unit:loop'], TemplateHelper::buildField($Field, $tpl, 'unit:loop'));
            }

            //----------------
            // category field
            if (!empty($cid) && config('column_list_category_on') === 'on') {
                if (config('column_list_category_field_on') === 'on') {
                    $Field = loadCategoryField($cid);
                } else {
                    $Field = new Field();
                }
                $Field->setField('fieldCategoryName', ACMS_RAM::categoryName($cid));
                $Field->setField('fieldCategoryCode', ACMS_RAM::categoryCode($cid));
                $Field->setField('fieldCategoryUrl', acmsLink(['cid' => $cid, '_protocol' => 'http'], false));
                $Field->setField('fieldCategoryId', $cid);

                $tpl->add(['categoryField', 'unit:loop'], TemplateHelper::buildField($Field, $tpl, 'unit:loop'));
            }

            $tpl->add('column:loop', $row);
            $tpl->add('unit:loop', $row);
        }
        return $vars;
    }

    /**
     * sqlの組み立て
     *
     * @return SQL_Select
     */
    function buildQuery()
    {
        $sql = SQL::newSelect('column');
        $sql->addLeftJoin('entry', 'entry_id', 'column_entry_id');
        $sql->addLeftJoin('category', 'category_id', 'entry_category_id');
        $sql->addLeftJoin('blog', 'blog_id', 'entry_blog_id');

        ACMS_Filter::blogTree($sql, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($sql);
        ACMS_Filter::categoryTree($sql, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($sql);

        $this->userFilterQuery($sql);
        $this->keywordFilterQuery($sql);
        $this->tagFilterQuery($sql);
        $this->fieldFilterQuery($sql);

        if (!empty($this->eid)) {
            $sql->addWhereOpr('column_entry_id', $this->eid);
        }
        ACMS_Filter::entrySession($sql);
        ACMS_Filter::entrySpan($sql, $this->start, $this->end);
        $sql->addWhereIn('column_type', array_merge(
            configArray('column_list_type'),
            configArray('column_list_extends_type')
        ));
        $this->setAmount($sql); // limitする前のクエリから全件取得のクエリを準備しておく
        $this->orderQuery($sql);
        $this->limitQuery($sql);

        return $sql;
    }

    /**
     * エントリー数取得sqlの準備
     *
     * @param SQL_Select $SQL
     * @return void
     */
    function setAmount($SQL)
    {
        $this->amount = new SQL_Select($SQL);
        $this->amount->addSelect('DISTINCT(`column_id`)', 'unit_amount', null, 'COUNT');
    }

    /**
     * orderクエリ組み立て
     *
     * @param SQL_Select & $SQL
     * @return void
     */
    function orderQuery(&$SQL)
    {
        $order = $this->config['order'][0];
        if ('random' === $order) {
            $SQL->setOrder('RAND()');
        } else {
            if ('datetime-asc' === $order) {
                $SQL->addOrder('entry_datetime', 'ASC');
            } else {
                $SQL->addOrder('entry_datetime', 'DESC');
            }
        }
    }

    /**
     * @return array
     */
    protected function dsn()
    {
        return dsn();
    }
}
