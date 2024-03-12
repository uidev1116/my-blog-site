<?php

class ACMS_GET_Touch_NotCategoryParent extends ACMS_GET
{
    function get()
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('category');
        $SQL->addSelect('category_id');
        $SQL->addWhereOpr('category_blog_id', BID);
        if (CID) {
            $SQL->addWhereOpr('category_parent', CID);
        }
        $q = $SQL->get(dsn());
        $id = $DB->query($q, 'one');
        if ($id) {
            return false;
        } else {
            return $this->tpl;
        }
    }
}
