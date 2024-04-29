<?php

class ACMS_GET_Admin_Module_Index extends ACMS_GET_Admin_Module
{
    public function get()
    {
        if (roleAvailableUser()) {
            if (!roleAuthorization('module_edit', BID)) {
                return '';
            }
        } else {
            if (!sessionWithAdministration()) {
                return '';
            }
        }

        $order  = ORDER ? ORDER : 'name-asc';
        $module = $this->Get->get('name', '');
        $scope  = $this->Get->get('axis', '');
        $ctpl   = $this->Get->get('tpl', '');
        $cmid   = intval($this->Get->get('mid', 0));

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

        $themes         = [];
        $theme          = config('theme');
        $tplModuleDir   = 'include/module/template/';
        while (!empty($theme)) {
            array_unshift($themes, $theme);
            $theme  = preg_replace('/^[^@]*?(@|$)/', '', $theme);
        }
        array_unshift($themes, 'system');

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
            } elseif (
                0
                or ( roleAvailableUser() && roleAuthorization('module_edit', $mbid) )
                or sessionWithAdministration($mbid)
            ) {
                $Tpl->add('notMinePermit', $this->getLinkVars($mbid, $row));
            } else {
                $Tpl->add('notMine');
            }

            //---------------
            // layout module
            $tplAry     = [];
            $tplLabels  = [];
            $fix        = false;
            foreach ($themes as $themeName) {
                $dir = SCRIPT_DIR . THEMES_DIR . $themeName . '/' . $tplModuleDir . $name . '/';
                if (Storage::isDirectory($dir)) {
                    $templateDir    = opendir($dir);
                    while ($tpl = readdir($templateDir)) {
                        preg_match('/(?:.*)\/(.*)(?:\.([^.]+$))/', $dir . $tpl, $info);
                        if (!isset($info[1]) || !isset($info[2])) {
                            continue;
                        }
                        $pattern = '/^(' . $info[1] . '|' . $info[1] . config('module_identifier_duplicate_suffix') . '.*)$/';
                        if (preg_match($pattern, $identifier)) {
                            $tplAry = [];
                            $fix    = true;
                            break;
                        }
                        if (
                            0
                            || strncasecmp($tpl, '.', 1) === 0
                            || $info[2] === 'yaml'
                        ) {
                            continue;
                        }
                        $tplAry[] = $tpl;
                    }
                    if ($labelAry = Config::yamlLoad($dir . 'label.yaml')) {
                        $tplLabels += $labelAry;
                    }
                }
            }
            $tplAry = array_unique($tplAry);

            $tplSort = [];
            foreach ($tplLabels as $tpl => $label) {
                $key = array_search($tpl, $tplAry, true);
                if ($key !== false) {
                    $tplSort[] = [
                        'template' => $tpl,
                        'tplLabel' => $label,
                    ];
                    unset($tplAry[$key]);
                }
            }
            foreach ($tplAry as $tpl) {
                $tplSort[] = [
                    'template' => $tpl,
                    'tplLabel' => $tpl,
                ];
            }
            foreach ($tplSort as $loop) {
                if (
                    1
                    && $mid === $cmid
                    && $ctpl === $loop['template']
                ) {
                    $loop['selected'] = config('attr_selected');
                }
                $Tpl->add(['template:loop', 'module:loop'], $loop);
            }
            if (empty($tplSort)) {
                if ($fix) {
                    $Tpl->add(['fixTmpl', 'module:loop']);
                } else {
                    $Tpl->add(['notEmptyTmpl', 'module:loop']);
                }
            }
            $Tpl->add('module:loop', $vars);
        } while ($row = $DB->fetch($q));

        $Tpl->add(null, $filter);

        return $Tpl->get();
    }

    function getLinkVars($bid, $module)
    {
        $mid    = intval($module['module_id']);
        $rid    = intval($this->Get->get('rid'));
        $name   = $module['module_name'];

        $linkVars   = [
            'itemUrl'   => acmsLink([
                'bid'   => $bid,
                'admin' => 'module_edit',
                'query' => [
                    'mid'   => $mid,
                    'rid'   => $rid,
                ],
            ]),
            'exportUrl' => acmsLink([
                'bid'   => $bid,
                'admin' => 'config_export',
                'query' => [
                    'mid'   => $mid,
                ],
            ]),
            'importUrl' => acmsLink([
                'bid'   => $bid,
                'admin' => 'config_import',
                'query' => [
                    'mid'   => $mid,
                ],
            ]),
        ];
        if (!in_array($name, ['Blog_Field', 'Entry_Field', 'Category_Field', 'User_Field', 'Module_Field'], true)) {
            $linkVars['configUrl'] = acmsLink([
                'bid'   => $bid,
                'admin' => 'config_' . strtolower(preg_replace('@(?<=[a-zA-Z0-9])([A-Z])@', '-$1', $name)),
                'query' => [
                    'mid'   => $mid,
                    'rid'   => $rid,
                ],
            ]);
        }
        return $linkVars;
    }
}
