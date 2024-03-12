<?php

class ACMS_GET_Admin_Rule_SelectGlobal extends ACMS_GET_Admin
{
    function get()
    {
        if (
            1
            && strpos(ADMIN, 'module_') === false
            && strpos(ADMIN, 'config_') === false
            && strpos(TPL, 'ajax/module') === false
        ) {
            return false;
        }
        if (strpos(ADMIN, 'config_set_') !== false) {
            return false;
        }
        $Tpl        = new Template($this->tpl, new ACMS_Corrector());
        $DB         = DB::singleton(dsn());
        $rootVars   = array();
        $rid        = $this->Get->get('rid');
        $query      = parseQuery(QUERY);

        if (!empty($rid)) {
            $rootVars['currentRule'] = ACMS_RAM::ruleName($rid);
        }

        $tmpQuery   = $query;
        unset($tmpQuery['rid']);
        $rootVars['defaultUrl'] = acmsLink(array(
            'bid'   => BID,
            'admin' => ADMIN,
            'query' => $tmpQuery,
        ), true);

        $SQL    = SQL::newSelect('rule');
        $SQL->addLeftJoin('blog', 'blog_id', 'rule_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');

        $Where  = SQL::newWhere();
        $Where->addWhereOpr('rule_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('rule_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);
        $SQL->addWhereOpr('rule_status', 'open');

        $SQL->setOrder('rule_sort');
        $all    = $DB->query($SQL->get(dsn()), 'all');

        $sort   = 1;
        while ($row = array_shift($all)) {
            $rid            = intval($row['rule_id']);
            $query['rid']   = $rid;
            $vars           = array(
                'rid'   => $rid,
                'label' => $row['rule_name'],
                'url'   => acmsLink(array(
                    'bid'   => BID,
                    'admin' => ADMIN,
                    'query' => $query,
                ), true),
            );
            $Tpl->add('rule:loop', $vars);

            $sort++;
        }
        $Tpl->add(null, $rootVars);

        return $Tpl->get();
    }

    function getLinkVars($bid, $rid)
    {
        return array(
            'itemUrl'   => acmsLink(array(
                'bid'   => $bid,
                'admin' => 'rule_edit',
                'query' => new Field(array(
                    'rid'   => $rid,
                )),
            )),
            'configUrl' => acmsLink(array(
                'bid'   => $bid,
                'admin' => 'config_index',
                'query' => new Field(array(
                    'rid'   => $rid,
                )),
            )),
            'moduleUrl' => acmsLink(array(
                'bid'   => $bid,
                'admin' => 'module_index',
                'query' => new Field(array(
                    'rid'   => $rid,
                )),
            )),
        );
    }
}
