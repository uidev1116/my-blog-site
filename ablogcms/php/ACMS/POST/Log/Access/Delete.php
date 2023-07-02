<?php

class ACMS_POST_Log_Access_Delete extends ACMS_POST
{
    function post()
    {
        $axis   = $this->Post->get('axis', 'self');

        $this->delete($axis);

        return $this->Post;
    }

    function delete($axis='self')
    {
        $DB     = DB::singleton(dsn());

        // term selected?
        $start  = $this->Post->get('target_span_start');
        $end    = $this->Post->get('target_span_end');
        if ( empty($start) || empty($end) ) {
            $this->Post->set('term_not_selected', true);
            return $this->Post;
        }

        @set_time_limit(0);

        if ( 'self' == $axis ) {
            $SQL    = SQL::newDelete('log_access');
            $SQL->addWhereOpr('log_access_blog_id', BID);
            $SQL->addWhereBw('log_access_datetime', $start, $end);
            $DB->query($SQL->get(dsn()), 'exec');
        } else {
            $SQL   = SQL::newSelect('blog');
            $SQL->addSelect('blog_id');
            ACMS_Filter::blogTree($SQL, BID, $axis);
            foreach ( $DB->query($SQL->get(dsn()), 'all') as $row ) {
                $bid    = intval($row['blog_id']);
                $SQL    = SQL::newDelete('log_access');
                $SQL->addWhereOpr('log_access_blog_id', $bid);
                $SQL->addWhereBw('log_access_datetime', $start, $end);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }
        return true;
    }

    public static function refresh()
    {
        $DB     = DB::singleton(dsn());

        $save_period = config('log_access_save_period');
        if ( RBID !== BID ) {
            $r_config = Config::loadBlogConfigSet(RBID);
            $log_access_save_period = $r_config->get('log_access_save_period');
            if ( !empty($log_access_save_period) ) {
                $save_period = $log_access_save_period;
            }
        }
        $save_period = intval($save_period) * -1;
        $save_period = strval($save_period);
        $expire = date("Y-m-d H:i:s",strtotime("$save_period day"));

        $SQL    = SQL::newDelete('log_access');
        $SQL->addWhereOpr('log_access_datetime', $expire, '<');
        $DB->query($SQL->get(dsn()), 'exec');
    }
}
