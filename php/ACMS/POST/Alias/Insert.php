<?php

class ACMS_POST_Alias_Insert extends ACMS_POST_Alias
{
    function post()
    {
        $Alias = $this->extract('alias');
        $Alias->setMethod('name', 'required');
        $Alias->setMethod('status', 'required');
        $Alias->setMethod('status', 'in', array('open', 'close'));
        $Alias->setMethod('indexing', 'required');
        $Alias->setMethod('indexing', 'in', array('on', 'off'));
        $Alias->setMethod('alias', 'operable', IS_LICENSED and sessionWithAdministration());

        $Alias->setMethod('domain', 'required');
        $Alias->setMethod('domain', 'domain', Blog::isDomain($Alias->get('domain'), $this->Get->get('aid'), true));
        $Alias->setMethod('scope', 'deny', $this->checkScope($Alias->get('scope')));
        $Alias->setMethod('code', 'exists', Blog::isCodeExists($Alias->get('domain'), $Alias->get('code')));
        $Alias->setMethod('code', 'reserved', !isReserved($Alias->get('code')));
        $Alias->setMethod('code', 'string', isValidCode($Alias->get('code')));

        $Alias->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB     = DB::singleton(dsn());

            //-----
            // aid
            $aid    = $DB->query(SQL::nextval('alias_id', dsn()), 'seq');

            //------
            // sort
            $SQL    = SQL::newSelect('alias');
            $SQL->setSelect('alias_sort');
            $SQL->addWhereOpr('alias_blog_id', BID);
            $SQL->setOrder('alias_sort', 'DESC');
            $sort   = max(intval($DB->query($SQL->get(dsn()), 'one')), ACMS_RAM::blogAliasSort(BID)) + 1;

            $SQL    = SQL::newInsert('alias');
            $SQL->addInsert('alias_id', $aid);
            $SQL->addInsert('alias_status', $Alias->get('status'));
            $SQL->addInsert('alias_sort', $sort);
            $SQL->addInsert('alias_domain', $Alias->get('domain'));
            $SQL->addInsert('alias_code', strval($Alias->get('code')));
            $SQL->addInsert('alias_name', $Alias->get('name'));
            $SQL->addInsert('alias_scope', $Alias->get('scope', 'local'));
            $SQL->addInsert('alias_indexing', $Alias->get('indexing'));
            $SQL->addInsert('alias_blog_id', BID);

            $DB->query($SQL->get(dsn()), 'exec');

            $this->Post->set('edit', 'insert');
        }

        return $this->Post;
    }
}

