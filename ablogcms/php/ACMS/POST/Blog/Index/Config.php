<?php

class ACMS_POST_Blog_Index_Config extends ACMS_POST_Blog
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('blog', 'isOperable', sessionWithAdministration());
        $this->Post->setMethod('checks', 'required');
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $targetBids = [];
            foreach ( $this->Post->getArray('checks') as $bid ) {
                if ( !($bid = idval($bid)) ) continue;
                if ( !(1
                    and ACMS_RAM::blogLeft(SBID) <= ACMS_RAM::blogRight($bid)
                    and ACMS_RAM::blogRight(SBID) >= ACMS_RAM::blogRight($bid)
                ) ) continue;
                $targetBids[] = $bid;
                $this->copyConfigToChild($bid);
                $this->Post->set('success', 'config');
            }
            AcmsLogger::info('「' . ACMS_RAM::blogName(BID) . '」ブログのコンフィグをコピーして子ブログに反映しました', [
                'targetBIDs' => implode(',', $targetBids),
            ]);
        } else {
            $this->Post->set('error', 'config_1');
        }

        return $this->Post;
    }

    function copyConfigToChild($cbid)
    {
        $DB = DB::singleton(dsn());
        $Config = loadConfig(BID, null, null);

        $config = array();
        foreach ( $Config->listFields() as $fd ) {
            $val    = $Config->getArray($fd);
            $config[$fd]    = (1 == count($val)) ? $val[0] : $val;
        }

        $SQL    = SQL::newDelete('config');
        $SQL->addWhereOpr('config_rule_id', null);
        $SQL->addWhereOpr('config_module_id', null);
        $SQL->addWhereOpr('config_blog_id', $cbid);
        $DB->query($SQL->get(dsn()), 'exec');

        Config::forgetCache(BID);

        $sort   = 1;
        foreach ( $config as $key => $vals ) {
            if ( empty($vals) ) continue;
            if ( !is_array($vals) ) $vals = array($vals);
            foreach ( $vals as $val ) {
                $SQL    = SQL::newInsert('config');
                $SQL->addInsert('config_key', $key);
                $SQL->addInsert('config_value', $val);
                $SQL->addInsert('config_sort', $sort++);
                $SQL->addInsert('config_rule_id', null);
                $SQL->addInsert('config_module_id', null);
                $SQL->addInsert('config_blog_id', $cbid);
                $DB->query($SQL->get(dsn()), 'exec');

                Config::forgetCache($bid);
            }
        }
    }
}
