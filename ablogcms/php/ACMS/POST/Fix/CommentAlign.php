<?php

class ACMS_POST_Fix_CommentAlign extends ACMS_POST
{
    function post()
    {
        if ( !sessionWithAdministration() ) die('error');
        @set_time_limit(0);
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('comment');
        $SQL->setSelect('comment_entry_id', 'eid', '', 'DISTINCT');
        $SQL->addWhereOpr('comment_blog_id', BID);
        $SQL->setOrder('eid');
        $entryQ = $SQL->get(dsn());
        if ( $DB->query($entryQ, 'fetch') ) { while ( !!($entryRow = $DB->fetch($entryQ)) ) {
            $eid    = intval($entryRow['eid']);

            $SQL    = SQL::newSelect('comment');
            $SQL->setSelect('comment_parent', 'pid', '', 'DISTINCT');
            $SQL->addWhereOpr('comment_entry_id', $eid);
            $SQL->addWhereOpr('comment_blog_id', BID);
            $SQL->setOrder('pid');
            $parentQ    = $SQL->get(dsn());
            $aryPid     = array();

            if ( $DB->query($parentQ, 'fetch') ) { while ( !!($parentRow = $DB->fetch($parentQ)) ) {
                $pid        = intval($parentRow['pid']);

                //-----
                // pos
                $pos    = 0;
                if ( !empty($pid) ) {
                    $SQL    = SQL::newSelect('comment');
                    $SQL->setSelect('comment_right');
                    $SQL->addWhereOpr('comment_id', $pid);
                    $pos    = intval($DB->query($SQL->get(dsn()), 'one')) - 1;
                }

                $SQL    = SQL::newSelect('comment');
                $SQL->setSelect('comment_id');
                $SQL->addWhereOpr('comment_parent', $pid);
                $SQL->addWhereOpr('comment_entry_id', $eid);
                $SQL->addWhereOpr('comment_blog_id', BID);
                $cnt    = 0;
                foreach ( $DB->query($SQL->get(dsn()), 'all') as $row ) {
                    $cnt++;
                    $cmid   = intval($row['comment_id']);

                    $right  = ($cnt*2) + $pos;
                    $left   = $right - 1;

                    $SQL    = SQL::newUpdate('comment');
                    $SQL->addUpdate('comment_left', $left);
                    $SQL->addUpdate('comment_right', $right);
                    $SQL->addWhereOpr('comment_id', $cmid);
                    $SQL->addWhereOpr('comment_entry_id', $eid);
                    $SQL->addWhereOpr('comment_blog_id', BID);
                    $DB->query($SQL->get(dsn()), 'exec');
                }

                if ( !empty($aryPid) ) {
                    $SQL    = SQL::newUpdate('comment');
                    $SQL->setUpdate('comment_left', SQL::newOpr('comment_left', ($cnt * 2), '+'));
                    $SQL->addWhereOpr('comment_left', $pos, '>');
                    $SQL->addWhereIn('comment_parent', $aryPid);
                    $SQL->addWhereOpr('comment_entry_id', $eid);
                    $SQL->addWhereOpr('comment_blog_id', BID);
                    $DB->query($SQL->get(dsn()), 'exec');

                    $SQL    = SQL::newUpdate('comment');
                    $SQL->setUpdate('comment_right', SQL::newOpr('comment_right', ($cnt * 2), '+'));
                    $SQL->addWhereOpr('comment_right', $pos, '>=');
                    $SQL->addWhereIn('comment_parent', $aryPid);
                    $SQL->addWhereOpr('comment_entry_id', $eid);
                    $SQL->addWhereOpr('comment_blog_id', BID);
                    $DB->query($SQL->get(dsn()), 'exec');
                }

                $aryPid[]   = $pid;
            } }
        } }

        die('finish');
    }
}
