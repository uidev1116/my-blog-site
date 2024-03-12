<?php

class ACMS_POST_Rule extends ACMS_POST
{
    protected function fix(&$Rule)
    {
        foreach (array('u', 'c', 'e') as $mode) {
            foreach (array('id', 'cd') as $type) {
                switch (strval($Rule->get($mode . $type . '_case'))) {
                    case '':
                    case 'IS NULL':
                    case 'IS NOT NULL':
                        $Rule->setField($mode . $type);
                        break;
                    default:
                        if (!$Rule->get($mode . $type)) {
                            $Rule->setField($mode . $type . '_case');
                        }
                }
            }
        }

        if (!$Rule->get('ua_case')) {
            $Rule->setField('ua');
        }

        return true;
    }

    protected function trim()
    {
        $DB = DB::singleton(dsn());

        $SQL = SQL::newSelect('rule');
        $SQL->addSelect('rule_id');
        $SQL->addWhereOpr('rule_blog_id', BID);
        $SQL->setOrder('rule_sort', 'ASC');

        foreach ($DB->query($SQL->get(dsn()), 'all') as $i => $rule) {
            $rid = $rule['rule_id'];

            $SQL = SQL::newUpdate('rule');
            $SQL->addUpdate('rule_sort', $i + 1);
            $SQL->addWhereOpr('rule_id', $rid);
            $SQL->addWhereOpr('rule_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            ACMS_RAM::rule($rid, null);
        }
    }
}
