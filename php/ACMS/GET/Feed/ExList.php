<?php

class ACMS_GET_Feed_ExList extends ACMS_GET
{
    function get()
    {
        $this->source           = config('feed_exlist_source');
        $this->limit            = intval(config('feed_exlist_limit'));
        $this->offset           = intval(config('feed_exlist_offset'));
        $this->newtime          = config('feed_exlist_newtime');
        $this->feed_exlist_cache_expire = config('feed_exlist_cache_expire');
        $this->mo_feed_exlist_notfound  = config('mo_feed_exlist_notfound');
        $this->kind             = config('feed_exlist_kind');

        $DB = DB::singleton(dsn());
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $feeds = array();

        //----------
        // cache
        $id        = md5($this->source);
        $criterion = date('Y-m-d H:i:s', strtotime('-'.$this->feed_exlist_cache_expire.' second'));

        if (!DEBUG_MODE && 'on' == config('cache')) {
            $SQL = SQL::newSelect('cache');
            $SQL->setSelect('cache_data');
            $SQL->addWhereOpr('cache_id', $id);
            $SQL->addWhereOpr('cache_expire', $criterion, '>', 'AND');
            $SQL->addWhereOpr('cache_blog_id', 0);

            if ($data = $DB->query($SQL->get(dsn()), 'one')) {
                $feeds = acmsUnserialize($data);
            }
        }

        if (empty($feeds)) {
            $RSS   = new FeedParser($this->source, $this->kind);
            $feeds = $RSS->get();

            $SQL = SQL::newDelete('cache');
            $SQL->addWhereOpr('cache_id', $id);
            $SQL->addWhereOpr('cache_blog_id', 0);
            $DB->query($SQL->get(dsn()), 'exec');

            if ( !empty($this->feed_exlist_cache_expire) ) {
                $expire = date('Y-m-d H:i:s', strtotime('+'.$this->feed_exlist_cache_expire.' second'));

                $SQL = SQL::newInsert('cache');
                $SQL->addInsert('cache_id', $id);
                $SQL->addInsert('cache_data', acmsSerialize($feeds));
                $SQL->addInsert('cache_expire', $expire);
                $SQL->addInsert('cache_blog_id', 0);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }

        //----------
        // notFound
        if ( empty($feeds['items']) ) {
            if ( $this->mo_feed_exlist_notfound == 'on' ) $Tpl->add('notFound');
            return $Tpl->get();
        }

        //----------
        // limit
        $limit  = count($feeds['items']) < $this->limit ? count($feeds['items']) : $this->limit;

        //----------
        // slice
        foreach ( array_slice($feeds['items'], $this->offset, $limit) as $row ) {
            if ( requestTime() <= @strtotime($row['datetime']) + $this->newtime ) {
                $Tpl->add('new');
            }
            $row += $this->buildDate(@$row['datetime'], $Tpl, 'item:loop');
            $Tpl->add('item:loop', $this->array_split($row));
        }

        $Tpl->add(null, $this->array_split($feeds['meta']));

        return $Tpl->get();
    }

    function array_split($array)
    {
        foreach ($array as $key => $val) {
            if ( is_array($val) ) {
                foreach ( $val as $_key => $_val ) {
                    $array[$key.'_'.$_key] = $_val;
                }
                unset($array[$key]);
            }
        }
        return $array;
    }
}
