<?php

class ACMS_POST_Category_Index_ConfigSet extends ACMS_POST
{
    function post()
    {
        $aryCid = $this->Post->getArray('checks');
        $setid = $this->Post->get('config_set_id', null);
        if (empty($setid)) {
            $setid = null;
        }

        $this->Post->reset(true);
        $this->Post->setMethod('category', 'operable', ( 1
            and sessionWithCompilation()
            and !empty($aryCid)
        ));
        $this->Post->validate();

        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newUpdate('category');
            $SQL->setUpdate('category_config_set_id', $setid);
            $SQL->addWhereIn('category_id', $aryCid);
            $DB->query($SQL->get(dsn()), 'exec');
            foreach ($aryCid as $cid) {
                ACMS_RAM::category($cid, null);
            }
        }
        return $this->Post;
    }
}
