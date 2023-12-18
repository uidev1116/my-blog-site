<?php

class ACMS_POST_Fix_UnitGroup extends ACMS_POST_Fix
{
    function post()
    {
        if ( !sessionWithAdministration() ) return false;
        $Fix = $this->extract('fix', new ACMS_Validator());
        $Fix->setMethod('unit_group_target', 'required');
        $Fix->setMethod('unit_group_fix', 'required');

        if ($this->Post->isValidAll()) {
            @set_time_limit(0);
            $DB = DB::singleton(dsn());

            $target = $Fix->get('unit_group_target');
            $value  = $Fix->get('unit_group_fix');

            $SQL    = SQL::newUpdate('column');
            $SQL->addUpdate('column_group', $value);
            $SQL->addWhereOpr('column_group', $target);
            $SQL->addWhereOpr('column_blog_id', BID);

            $DB->query($SQL->get(dsn()), 'exec');

            $this->Post->set('message', 'success');

            AcmsLogger::info('データ修正ツールで、ユニットグループ値を変更しました「' . $target . '」->「' . $value . '」');
        }

        return $this->Post;
    }
}
