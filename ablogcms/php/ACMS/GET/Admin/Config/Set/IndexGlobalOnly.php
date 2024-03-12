<?php

class ACMS_GET_Admin_Config_Set_IndexGlobalOnly extends ACMS_GET_Admin_Config_Set_Index
{
    protected function buildQuery()
    {
        $SQL = SQL::newSelect('config_set');
        $SQL->addLeftJoin('blog', 'blog_id', 'config_set_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');
        $SQL->addWhereOpr('config_set_scope', 'global');
        $SQL->setOrder('config_set_sort', 'ASC');

        return $SQL;
    }
}
