<?php

use Acms\Services\Facades\Module;

class ACMS_GET_Admin_Module_Index extends ACMS_GET_Admin_Module
{
    public function get()
    {
        if (!Module::canUpdate(BID)) {
            return '';
        }

        $order  = ORDER ? ORDER : 'name-asc';
        $module = $this->Get->get('name', '');

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());

        if (
            1
            && !$this->Post->isNull()
            && $this->Post->get('refreshed') === 'refreshed'
        ) {
            $Tpl->add('refreshed');
        } elseif ($error = $this->Post->get('error')) {
            $Tpl->add('error#' . $error);
        }

        $SQL    = SQL::newSelect('module');
        $SQL->addLeftJoin('blog', 'blog_id', 'module_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');

        $Where  = SQL::newWhere();
        $Where->addWhereOpr('module_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('module_scope', 'global', '=', 'OR');
        $SQL->addWhereOpr('module_label', 'crm-module-indexing-hidden', '<>');
        if (!ADMIN) {
            $SQL->addWhereOpr('module_status', 'open');
        }

        $SQL->addWhere($Where);

        //---------------
        // layout module
        if (ADMIN !== 'module_index') {
            $SQL->addWhereOpr('module_layout_use', '1');
        }

        //--------
        // module
        if (!empty($module)) {
            $SQL->addWhereOpr('module_name', $module);
            $filter['name:selected#' . $module]    = config('attr_selected');
        }

        //-------
        // order
        $filter['order:selected#' . $order]  = config('attr_selected');
        $opr    = explode('-', $order);
        $SQL->addOrder('module_blog_id', 'ASC');
        $SQL->addOrder('module_' . $opr[0], ucwords($opr[1]));
        $SQL->addOrder('module_id', ucwords($opr[1]));

        $q = $SQL->get(dsn());

        if (!$DB->query($q, 'fetch') or !($row = $DB->fetch($q))) {
            $Tpl->add('index#notFound');
            $Tpl->add(null, $filter);
            return $Tpl->get();
        }

        do {
            $mid        = intval($row['module_id']);
            $name       = $row['module_name'];
            $identifier = $row['module_identifier'];

            $Tpl->add('status#' . $row['module_status']);

            if (BID !== intval($row['module_blog_id'])) {
                $row['module_scope'] = 'parental';
            }
            $Tpl->add('scope:touch#' . $row['module_scope']);

            $mbid   = intval($row['module_blog_id']);
            $vars   = [
                'mid'       => $mid,
                'bid'       => $mbid,
                'identifier' => $identifier,
                'label'     => $row['module_label'],
                'name'      => $name,
                'scope'     => $row['module_scope'],
            ];

            if (BID === $mbid) {
                $Tpl->add('mine', $this->getLinkVars(BID, $row));
            } elseif (Module::canUpdate($mbid)) {
                $Tpl->add('notMinePermit', $this->getLinkVars($mbid, $row));
            } else {
                $Tpl->add('notMine');
            }

            $Tpl->add('module:loop', $vars);
        } while ($row = $DB->fetch($q));

        $Tpl->add(null, $filter);

        return $Tpl->get();
    }

    /**
     * モジュールの編集画面のリンクを生成する
     *
     * @param int $bid ブログID
     * @param array{module_id: int, module_name: string} $module モジュール情報
     * @return array{itemUrl: string, exportUrl: string, importUrl: string, configUrl?: string} リンク変数
     */
    protected function getLinkVars($bid, $module)
    {
        $mid    = intval($module['module_id']);
        $rid    = intval($this->Get->get('rid'));
        $name   = $module['module_name'];

        $linkVars   = [
            'itemUrl'   => (string)acmsLink([
                'bid'   => $bid,
                'admin' => 'module_edit',
                'query' => [
                    'mid'   => $mid,
                    'rid'   => $rid,
                ],
            ]),
            'exportUrl' => (string)acmsLink([
                'bid'   => $bid,
                'admin' => 'config_export',
                'query' => [
                    'mid'   => $mid,
                ],
            ]),
            'importUrl' => (string)acmsLink([
                'bid'   => $bid,
                'admin' => 'config_import',
                'query' => [
                    'mid'   => $mid,
                ],
            ]),
        ];
        if (!in_array($name, ['Blog_Field', 'Entry_Field', 'Category_Field', 'User_Field', 'Module_Field'], true)) {
            $linkVars['configUrl'] = (string)acmsLink([
                'bid'   => $bid,
                'admin' => 'config_' . strtolower((string)preg_replace('@(?<=[a-zA-Z0-9])([A-Z])@', '-$1', $name)),
                'query' => [
                    'mid'   => $mid,
                    'rid'   => $rid,
                ],
            ]);
        }
        return $linkVars;
    }
}
