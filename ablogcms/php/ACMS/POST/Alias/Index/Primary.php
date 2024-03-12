<?php

class ACMS_POST_Alias_Index_Primary extends ACMS_POST
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('primary', 'required');

        if (!($aid = intval($this->Post->get('primary')))) {
            $aid = null;
        }
        $this->Post->setMethod('alias', 'operable', sessionWithAdministration());
        $this->Post->setMethod('alias', 'status', 'open' == ($aid ? ACMS_RAM::aliasStatus($aid) : ACMS_RAM::blogAliasStatus(BID)));

        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newUpdate('blog');
            $SQL->addUpdate('blog_alias_primary', $aid);
            $SQL->addWhereOpr('blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::blog(BID, null);
            //--------------
            // update cache
            ACMS_RAM::setBlogAliasPrimary(BID, $aid);

            AcmsLogger::info('エイリアスのプライマリを変更しました', [
                'primary_alias_id' => $aid,
                'bid' => BID,
            ]);
        }

        return $this->Post;
    }
}
