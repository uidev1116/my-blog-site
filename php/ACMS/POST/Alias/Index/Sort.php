<?php

class ACMS_POST_Alias_Index_Sort extends ACMS_POST
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('checks', 'required');
        $this->Post->setMethod('alias', 'operable', sessionWithAdministration());    
        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            $fromAry = $this->fix();

            $DB     = DB::singleton(dsn());
            $aids   = $this->Post->getArray('checks');

            foreach ( $aids as $aid ) {
                $aid    = intval($aid);
                if ( !($to = intval($this->Post->get('sort-'.$aid))) ) { continue; }
                if ( $aid == 0 || !($from = $fromAry[$aid]) ) { continue; }
                if ( $to === $from ) { continue; }

                //------------
                // alias sort
                $SQL    = SQL::newUpdate('alias');
                $SQL->setUpdate('alias_sort', SQL::newOpr('alias_sort', 1, ($from < $to) ? '-' : '+'));
                $SQL->addWhereBw('alias_sort', min($from, $to), max($from, $to));
                $SQL->addWhereNotIn('alias_id', $aids);
                $SQL->addWhereOpr('alias_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
      
                //-----------------
                // blog alias sort
                $SQL    = SQL::newUpdate('blog');
                $SQL->setUpdate('blog_alias_sort', SQL::newOpr('blog_alias_sort', 1, ($from < $to) ? '-' : '+'));
                $SQL->addWhereBw('blog_alias_sort', min($from, $to), max($from, $to));
                $SQL->addWhereOpr('blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
                ACMS_RAM::blog(BID, null);
                
                if ( $aid ) {
                    $SQL    = SQL::newUpdate('alias');
                    $SQL->setUpdate('alias_sort', $to);
                    $SQL->addWhereOpr('alias_id', $aid);
                    $SQL->addWhereOpr('alias_blog_id', BID);
                    $DB->query($SQL->get(dsn()), 'exec');
                } else {
                    $SQL    = SQL::newUpdate('blog');
                    $SQL->addUpdate('blog_alias_sort', $to);
                    $SQL->addWhereOpr('blog_id', BID);
                    ACMS_RAM::blog(BID, null);
                }
                ACMS_RAM::alias($aid, null);
            }

            $SQL    = SQL::newSelect('blog');
            $SQL->setSelect('blog_alias_sort');
            $SQL->addWhereOpr('blog_id', BID);
            ACMS_RAM::_mapping('blog_alias_sort', BID, intval($DB->query($SQL->get(dsn()), 'one')));
        }

        return $this->Post;
    }

    function fix()
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('alias');
        $SQL->addSelect('alias_id');
        $SQL->addWhereOpr('alias_blog_id', BID);

        $fromAry = array();

        foreach ( $DB->query($SQL->get(dsn()), 'all') as $alias ) {
            $aid = $alias['alias_id'];
            if ( !($sort = intval($this->Post->get('sort-current-'.$aid))) ) { continue; }
            $fromAry[$aid] = $sort;

            $SQL = SQL::newUpdate('alias');
            $SQL->addUpdate('alias_sort', $sort);
            $SQL->addWhereOpr('alias_id', $aid);
            $SQL->addWhereOpr('alias_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
            ACMS_RAM::alias($aid, null);
        }

        return $fromAry;
    }
}

