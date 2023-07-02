<?php

class ACMS_POST_Alias_Update extends ACMS_POST_Alias
{
    function post()
    {
        $Alias = $this->extract('alias');
        $Alias->setMethod('name', 'required');
        $Alias->setMethod('status', 'required');
        $Alias->setMethod('status', 'in', array('open', 'close'));
        $Alias->setMethod('indexing', 'in', array('on', 'off'));
        $Alias->setMethod('alias', 'operable', IS_LICENSED and sessionWithAdministration() and $aid = intval($this->Get->get('aid')));

        $Alias->setMethod('domain', 'required');
        $Alias->setMethod('domain', 'domain', Blog::isDomain($Alias->get('domain'), $this->Get->get('aid'), true, true));
        $Alias->setMethod('scope', 'deny', $this->checkScope($Alias->get('scope')));
        $Alias->setMethod('code', 'exists', Blog::isCodeExists($Alias->get('domain'), $Alias->get('code'), BID, $aid));
        $Alias->setMethod('code', 'reserved', !isReserved($Alias->get('code')));
        $Alias->setMethod('code', 'string', isValidCode($Alias->get('code')));

        $Alias->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $DB = DB::singleton(dsn());
            $SQL = SQL::newUpdate('alias');
            $SQL->addUpdate('alias_status', $Alias->get('status'));
            $SQL->addUpdate('alias_domain', $Alias->get('domain'));
            $SQL->addUpdate('alias_code', strval($Alias->get('code')));
            $SQL->addUpdate('alias_name', $Alias->get('name'));
            $SQL->addUpdate('alias_scope', $Alias->get('scope', 'local'));
            $SQL->addUpdate('alias_indexing', $Alias->get('indexing', 'on'));
            $SQL->addUpdate('alias_blog_id', BID);
            $SQL->addWhereOpr('alias_blog_id', BID);
            $SQL->addWhereOpr('alias_id', $aid);
            $DB->query($SQL->get(dsn()), 'exec');

            ACMS_RAM::alias($aid, null);

            $this->Post->set('edit', 'update');
        }

        return $this->Post;
    }
}

