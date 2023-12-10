<?php

class ACMS_POST_Fix_UnitMap extends ACMS_POST_Fix
{
    function post()
    {
        if (!sessionWithAdministration()) {
            return false;
        }

        $Fix = $this->extract('fix', new ACMS_Validator());
        $Fix->setMethod('fix_map_type_target', 'required');
        $Fix->setMethod('fix_map_type_change_value', 'required');
        $Fix->setMethod('fix_image_size', 'in', array('osmap', 'map'));

        if ($this->Post->isValidAll()) {
            @set_time_limit(0);

            $target = $Fix->get('fix_map_type_target');
            $value = $Fix->get('fix_map_type_change_value');

            $this->sameType($target, $value);
            $this->sameType($target, $value, true);
            $this->Post->set('message', 'success');

            AcmsLogger::info('データ修正ツールで、マップユニットの種類を変更しました「' . $target . '」->「' . $value . '」');
        }

        return $this->Post;
    }

    protected function sameType($target, $value, $rev = false)
    {
        $table = $rev ? 'column_rev' : 'column';

        $DB = DB::singleton(dsn());
        $SQL = SQL::newUpdate($table);
        $SQL->addWhereOpr('column_type', $target);
        $SQL->addWhereOpr('column_blog_id', BID);
        $SQL->addUpdate('column_type', $value);
        $DB->query($SQL->get(dsn()), 'exec');
    }
}
