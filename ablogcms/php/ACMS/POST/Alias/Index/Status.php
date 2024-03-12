<?php

class ACMS_POST_Alias_Index_Status extends ACMS_POST
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('status', 'required');
        $this->Post->setMethod('status', 'in', array('open', 'close'));
        $this->Post->setMethod('alias', 'operable', sessionWithAdministration());
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $aids = $this->Post->getArray('checks');
            foreach ($aids as $aid) {
                if (!($aid = intval($aid))) {
                    $aid = null;
                }
                if ($aid !== ACMS_RAM::blogAliasPrimary(BID)) { // except primary
                    if ($aid) {
                        $SQL    = SQL::newUpdate('alias');
                        $SQL->addUpdate('alias_status', $this->Post->get('status'));
                        $SQL->addWhereOpr('alias_id', $aid);
                        $SQL->addWhereOpr('alias_blog_id', BID);
                        $DB->query($SQL->get(dsn()), 'exec');
                        ACMS_RAM::alias($aid, null);
                    } else {
                        $SQL    = SQL::newUpdate('blog');
                        $SQL->addUpdate('blog_alias_status', $this->Post->get('status'));
                        $SQL->addWhereOpr('blog_id', BID);
                        $DB->query($SQL->get(dsn()), 'exec');
                        ACMS_RAM::blog(BID, null);

                        //--------------
                        // update cache
                        ACMS_RAM::_mapping('blog_alias_status', BID, $this->Post->get('status'));
                    }
                }
            }
            AcmsLogger::info('エイリアスのステータスを一括変更しました', [
                'aid' => implode(',', $aids),
                'bid' => BID,
                'status' => $this->Post->get('status'),
            ]);
        }

        return $this->Post;
    }
}
