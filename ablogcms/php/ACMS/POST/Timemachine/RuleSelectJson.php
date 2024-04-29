<?php

class ACMS_POST_Timemachine_RuleSelectJson extends ACMS_POST
{
    function post()
    {
        if (!timemachineAuth()) {
            return false;
        }
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('rule');
        $SQL->addLeftJoin('blog', 'blog_id', 'rule_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');
        $Where = SQL::newWhere();
        $Where->addWhereOpr('rule_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('rule_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);
        $SQL->addWhereOpr('rule_status', 'open');
        $SQL->setOrder('rule_sort');
        $all = $DB->query($SQL->get(dsn()), 'all');

        $json = [];
        while ($row = array_shift($all)) {
            $rid = intval($row['rule_id']);
            $json[] = [
                'id' => $rid,
                'label' => $row['rule_name'],
            ];
        }
        Common::responseJson($json);
    }
}
