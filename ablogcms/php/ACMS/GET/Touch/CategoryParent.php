<?php

class ACMS_GET_Touch_CategoryParent extends ACMS_GET
{
    public function get()
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
            return $this->tpl;
        } else {
            return '';
        }
    }
}
