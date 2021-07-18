<?php

class ACMS_GET_Admin_Dashboard_ClearCache extends ACMS_GET
{
    function get()
    {
        if ( !sessionWithAdministration() ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('cache_reserve');
        $SQL->addLeftJoin('entry', 'cache_reserve_entry_id', 'entry_id');
        $SQL->addWhereOpr('cache_reserve_blog_id', BID);
        $SQL->setOrder('cache_reserve_datetime', 'ASC');
        $SQL->setLimit(100);
        $all    = $DB->query($SQL->get(dsn()), 'all');

        foreach ( $all as $row ) {
            $reserve = array(
                'title'     => $row['entry_title'],
                'datetime'  => $row['cache_reserve_datetime'],
                'type'      => $row['cache_reserve_type'],
                'entryUrl'  => acmsLink(array(
                    'bid'   => $row['entry_blog_id'],
                    'eid'   => $row['entry_id'],
                )),
                'entryEdit' => acmsLink(array(
                    'bid'   => $row['entry_blog_id'],
                    'eid'   => $row['entry_id'],
                    'admin' => 'entry_editor',
                )),
            );
            $reserveVal     = $this->buildField(new Field($reserve), $Tpl, array('cache_reserve:loop'));
            $Tpl->add('cache_reserve:loop', $reserveVal);
        }


        $BLOB   = SQL::newselect('cache');
        $BLOB->addSelect('cache_data', 'cache_total_binaries', null , 'count');
        $BLOB->addSelect(SQL::newFunction('cache_data', 'LENGTH'), 'cache_total_bytes', null , 'SUM');
        $BLOB->addWhereOpr('cache_status', 'generated');
        $BLOB->addWhereOpr('cache_blog_id', BID);
        $BLOB->addWhereOpr('cache_expire', date('Y-m-d H:i:s', REQUEST_TIME), '>');
        $q = $BLOB->get(dsn());

        $blob = $DB->query($q, 'row');
        
        $cacheAmount = $blob['cache_total_binaries'];
        $totalBytes = $blob['cache_total_bytes'];
        
        if ( strlen($totalBytes) >= 8 ) {
            $totalBytes = $totalBytes/1024;
            $totalBytes = number_format($totalBytes/1024);
            $totalBytes = $totalBytes.'M';
        } elseif ( strlen($totalBytes) >= 4 ) {
            $totalBytes = number_format($totalBytes/1024);
            $totalBytes = $totalBytes.'K';
        } elseif ( empty($totalBytes) ) {
            $totalBytes = '0';
        }
                
        $vars = array('totalBytes' => $totalBytes, 'totalBins' => $cacheAmount);

        if ( config('cache') === 'on' ) {
            $Tpl->add('cache_enable');
        } else {
            $Tpl->add('cache_disable');
        }

        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
