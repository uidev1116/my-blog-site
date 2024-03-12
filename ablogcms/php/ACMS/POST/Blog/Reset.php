<?php

class ACMS_POST_Blog_Reset extends ACMS_POST_Blog
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('blog', 'operable', sessionWithAdministration());
        $this->Post->validate();
        if ($this->Post->isValidAll()) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newDelete('config');
            $SQL->addWhereOpr('config_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            Config::forgetCache(BID);

            $this->Post->set('msg', 'reset');

            AcmsLogger::info('「' . ACMS_RAM::blogName(BID) . '」ブログのコンフィグを削除しました');
        }

        return $this->Post;
    }
}
