<?php

class ACMS_POST_Alias_Delete extends ACMS_POST_Alias
{
    function post()
    {
        $this->Post->setMethod('alias', 'operable',
            ($aid = intval($this->Get->get('aid'))) and sessionWithAdministration()
        );
        $this->Post->setMethod('alias', 'primary', ACMS_RAM::blogAliasPrimary(BID) <> $aid);
        $this->Post->validate();

        if ( $this->Post->isValidAll() and $sort = ACMS_RAM::aliasSort($aid) ) {
            $DB = DB::singleton(dsn());
            $name = ACMS_RAM::aliasName($aid);

            //------------
            // alias sort
            $SQL    = SQL::newUpdate('alias');
            $SQL->setUpdate('alias_sort', SQL::newOpr('alias_sort', 1, '-'));
            $SQL->addWhereOpr('alias_sort', $sort, '>');
            $DB->query($SQL->get(dsn()), 'exec');

            //-----------
            // blog sort
            $blogAliasSort  = ACMS_RAM::blogAliasSort(BID);
            if ( $sort < $blogAliasSort ) {
                $SQL    = SQL::newUpdate('blog');
                $SQL->setUpdate('blog_alias_sort', $blogAliasSort - 1);
                $SQL->addWhereOpr('blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::blog(BID, null);
            }

            //--------
            // delete
            $SQL    = SQL::newDelete('alias');
            $SQL->addWhereOpr('alias_id', $aid);
            $SQL->addWhereOpr('alias_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::alias($aid, null);

            $this->Post->set('edit', 'delete');

            AcmsLogger::info('エイリアス「' . $name . '」を削除しました', [
                'aid' => $aid,
                'name' => $name,
            ]);
        }

        return $this->Post;
    }
}
