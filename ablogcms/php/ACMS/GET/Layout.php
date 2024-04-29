<?php

class ACMS_GET_Layout extends ACMS_GET
{
    private static $onlyLayout  = false;
    public $aryTypeLabel           = [];

    function build($Doc = '', $parentID = 0, $parentHash = '', $colNum = 1)
    {
        //---------
        // extract
        $gridDataAry = $this->getGridDataAry($parentID, $colNum);

        //-------
        // build
        foreach ($gridDataAry as $data) {
            $Tpl    = new Template($this->tpl, new ACMS_Corrector());
            $vars   = $data;
            $id     = uniqueString();
            $sid    = $data['serial'];

            if (!empty($vars)) {
                $vars['id'] = $id;
                $type   = 'type#' . $vars['class'];
                $count  = count(explode('-', $vars['class']));

                if (!empty($parentID)) {
                    $vars['parent'] = $parentHash;
                }

                $typeVars = $vars;
                if (LAYOUT_EDIT && !LAYOUT_PREVIEW) {
                    $class  = $data['class'];
                    $label  = isset($this->aryTypeLabel[$class]) ? $this->aryTypeLabel[$class] : '';
                    $typeVars['blockLabel']  = $label;

                    if ($type === 'type#module') {
                        $Tpl->add(['moduleLabel', 'block:loop']);
                    } else {
                        $vars['label']  = $label;
                    }
                }

                $Tpl->add([$type, 'block:loop'], $typeVars);


                $Tpl->add('block:loop', $vars);
                $Box    = $Tpl->get();
                if (!!$data['mid']) {
                    $Box = $this->module($Box, $data);
                }

                for ($i = 1; $i <= $count; $i++) {
                    $Child = $this->build('', $sid, $id, $i);
                    $pattern = '<!-- COL#' . $i . ' -->';
                    $Box = str_replace($pattern, $Child, $Box);
                }
                $Doc .= $Box;
            }
        }
        return $Doc;
    }

    function getGridDataAry($parent, $col)
    {
        static $Map;

        if (empty($Map)) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('layout_grid');
            $SQL->addWhereOpr('layout_grid_identifier', $this->identifier);
            if (LAYOUT_PREVIEW) {
                $SQL->addWhereOpr('layout_grid_preview', 1);
            } else {
                $SQL->addWhereOpr('layout_grid_preview', 0);
            }
            if (config('layout_blog_free') !== 'on') {
                $SQL->addWhereOpr('layout_grid_blog_id', BID);
            }
            $SQL->setOrder('layout_grid_row', 'ASC');
            $all    = $DB->query($SQL->get(dsn()), 'all');
            if (empty($all)) {
                return [];
            } else {
                foreach ($all as $data) {
                    $row = [];
                    foreach ($data as $key => $val) {
                        $row[str_replace('layout_grid_', '', $key)] = $val;
                    }
                    $Map[$row['parent']][$row['col']][] = $row;
                }
            }
        }

        if (isset($Map[$parent][$col])) {
            return $Map[$parent][$col];
        }
        return [];
    }

    function module($tpl, $layout)
    {
        static $Map;

        if (empty($Map)) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('layout_grid');
            $SQL->addSelect('module_id');
            $SQL->addSelect('module_name');
            $SQL->addSelect('module_identifier');
            $SQL->addSelect('layout_grid_tpl');
            $SQL->addLeftJoin('module', 'layout_grid_mid', 'module_id');
            $SQL->addWhereOpr('layout_grid_identifier', $this->identifier);
            $SQL->addWhereOpr('layout_grid_preview', $layout['preview']);
            if ($all = $DB->query($SQL->get(dsn()), 'all')) {
                foreach ($all as $row) {
                    $mid    = strval($row['module_id']);
                    $Map[$mid] = [
                        'mid'           => $row['module_id'],
                        'name'          => $row['module_name'],
                        'identifier'    => $row['module_identifier'],
                    ];
                }
            }
        }

        $mid    = strval($layout['mid']);
        if (isset($Map[$mid])) {
            $moduleName = $Map[$mid]['name'];
            $moduleID   = $Map[$mid]['identifier'];
            $moduleTpl  = $layout['tpl'];

            $mTpl       = $this->spreadModule($moduleName, $moduleID, $moduleTpl);
            $pattern    = '<!-- MODULE -->';
            $tpl        = str_replace($pattern, $mTpl, $tpl);
        }
        return $tpl;
    }

    function spreadModule($moduleName, $moduleID, $moduleTpl)
    {
        return Tpl::spreadModule($moduleName, $moduleID, $moduleTpl, (get_class($this) === 'ACMS_GET_Layout' && self::$onlyLayout));
    }

    function srcUrl()
    {
        $Get    = $this->Get;
        $query  = '';
        $url    = HTTP_REQUEST_URL;

        if (!$Get->isNull()) {
            foreach ($Get->listFields() as $fd) {
                if ($fd === 'layout' || !$aryVal = $Get->get($fd)) {
                    continue;
                }
                $query  .= ($fd . '=' . $aryVal);
            }
        }
        if (!empty($query)) {
            $url .= ('?' . $query);
        }

        return $url;
    }

    function get()
    {
        $Tpl                = new Template($this->tpl, new ACMS_Corrector());
        $response           = '';
        self::$onlyLayout   = (UA === ONLY_BUILD_LAYOUT);

        if (LAYOUT_EDIT && !LAYOUT_PREVIEW) {
            if (!sessionWithAdministration()) {
                return '';
            }

            foreach (configArray('layout_add_type') as $i => $mode) {
                $label  = config('layout_add_type_label', '', $i);
                $this->aryTypeLabel[$mode] = $label;
            }

            if (preg_match('/<!-- BEGIN edit -->(.*)<!-- END edit -->/s', $this->tpl, $matches)) {
                $editTpl    = $matches[1];
                if (preg_match('/<!-- BEGIN block:loop -->(.*)<!-- END block:loop -->/s', $editTpl, $matches)) {
                    $this->tpl  = $matches[0];
                    $response   = $this->build();
                    $response   = preg_replace('/<!-- BEGIN block:loop -->(.*)<!-- END block:loop -->/s', str_replace('$', '\$', $response), $editTpl);
                }
                $response = str_replace('{preview}', acmsLink([
                    'bid'   => BID,
                    'cid'   => CID,
                    'eid'   => EID,
                    'tpl'   => 'ajax/layout/preview.html',
                    'query' => [
                        'preview'   => 'enable',
                        'url'       => $this->srcUrl(),
                    ],
                ], true), $response);
                $response = str_replace('{url}', $this->srcUrl(), $response);
            }
        } else {
            if (preg_match('/<!-- BEGIN display -->(.*)<!-- END display -->/s', $this->tpl, $matches)) {
                $this->tpl  = $matches[1];
                $response   = $this->build();

                //--------------
                // build module
                $response   = build($response, Field_Validation::singleton('post'));
            }
        }

        return $response;
    }

    static function formatBlock(&$mTpl, $type)
    {
        if ($type === 'dummy') {
            $mTpl    = preg_replace(
                [
                    '/<!--[\t 　]*BEGIN[\t 　]+layout\#display[^>]*?-->.*<!--[\t 　]*END[\t 　]+layout\#display[^>]*?-->/is',
                    '/<!--[\t 　]*(BEGIN|END)[\t 　]+layout\#dummy[^>]*?-->/is',
                ],
                [
                    '',
                    '',
                ],
                $mTpl
            );
        } else {
            $mTpl    = preg_replace(
                [
                    '/<!--[\t 　]*BEGIN[\t 　]+layout\#dummy[^>]*?-->.*<!--[\t 　]*END[\t 　]+layout\#dummy[^>]*?-->/is',
                    '/<!--[\t 　]*(BEGIN|END)[\t 　]+layout\#display[^>]*?-->/is',
                ],
                [
                    '',
                    '',
                ],
                $mTpl
            );
        }
    }
}
