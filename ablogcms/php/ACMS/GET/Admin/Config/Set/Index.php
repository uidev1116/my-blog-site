<?php

class ACMS_GET_Admin_Config_Set_Index extends ACMS_GET_Admin
{
    function get()
    {
        if (!$this->validate()) {
            return '';
        }

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $DB = DB::singleton(dsn());

        if (!$this->Post->isNull()) {
            $Tpl->add('refresh');
        }
        $SQL = $this->buildQuery();
        if (!$all = $DB->query($SQL->get(dsn()), 'all')) {
            return $Tpl->get();
        }
        $this->build($Tpl, $all);
        return $Tpl->get();
    }

    protected function validate()
    {
        if (!sessionWithContribution()) {
            return false;
        }
        return true;
    }

    protected function buildQuery()
    {
        $SQL = SQL::newSelect('config_set');
        $SQL->addLeftJoin('blog', 'blog_id', 'config_set_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');
        $Where = SQL::newWhere();
        $Where->addWhereOpr('config_set_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('config_set_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);
        $SQL->setOrder('config_set_sort', 'ASC');

        return $SQL;
    }

    protected function build(& $Tpl, $all)
    {
        $cnt = count($all);
        $sort = 1;
        while ($row = array_shift($all)) {
            $setid = intval($row['config_set_id']);
            if (BID !== intval($row['config_set_blog_id'])) {
                $row['config_set_scope'] = 'parental';
                $disabled = config('attr_disabled');
            } else {
                $disabled = '';
            }
            $Tpl->add('scope:touch#' . $row['config_set_scope']);

            for ($i = 1; $i <= $cnt; $i++) {
                $vars = array(
                    'value' => $i,
                    'label' => $i,
                );
                if ($sort == $i) {
                    $vars['selected'] = config('attr_selected');
                }
                $Tpl->add('sort:loop', $vars);
            }

            $vars = array(
                'setid' => $setid,
                'sort' => $sort,
                'scope' => $row['config_set_scope'],
                'name' => $row['config_set_name'],
                'description' => $row['config_set_description'],
                'disabled' => $disabled,
            );

            $setbid = intval($row['config_set_blog_id']);
            if (BID === $setbid) {
                $Tpl->add('mine', $this->getLinkVars(BID, $setid));
            } else {
                if (0
                    or (roleAvailableUser() && roleAuthorization('rule_edit', $setbid))
                    or sessionWithAdministration($setbid)
                ) {
                    $Tpl->add('notMinePermit', $this->getLinkVars($setbid, $setid));
                } else {
                    $Tpl->add('notMine');
                }
            }
            $Tpl->add('config_set:loop', $vars);

            $sort++;
        }
    }

    protected function getLinkVars($bid, $setid)
    {
        return array(
            'configSetId' => $setid,
            'itemUrl' => acmsLink(array(
                'bid' => $bid,
                'admin' => 'config_set_edit',
                'query' => new Field(array(
                    'setid' => $setid,
                )),
            )),
            'configUrl' => acmsLink(array(
                'bid' => $bid,
                'admin' => 'config_index',
                'query' => new Field(array(
                    'setid' => $setid,
                    'rid'   => $this->Get->get('rid'),
                )),
            ))
        );
    }
}

