<?php

class ACMS_POST_Fix_BlogAlign extends ACMS_POST
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('align', 'operable', (1
            and sessionWithAdministration()
            and 0 === ACMS_RAM::blogParent(BID)
        ));
        $this->Post->validate();

        if ($this->Post->isValid()) {
            @set_time_limit(0);
            $DB = DB::singleton(dsn());

            $que    = array(0);    // FIFO
            $aryPid = array();
            while (true) {
                $pid    = array_shift($que);

                $pos    = 0;
                if (!empty($pid)) {
                    $SQL    = SQL::newSelect('blog');
                    $SQL->addWhereOpr('blog_id', $pid);
                    if (!!($row = $DB->query($SQL->get(dsn()), 'row'))) {
                        $pos    = intval($row['blog_left']);// - 1;
                    } else {
                        continue;
                    }
                }

                $SQL    = SQL::newSelect('blog');
                $SQL->setSelect('blog_id');
                $SQL->addWhereOpr('blog_parent', $pid);
                $SQL->setOrder('blog_sort');

                $cnt    = 0;
                if (!!($all = $DB->query($SQL->get(dsn()), 'all'))) {
                    foreach ($DB->query($SQL->get(dsn()), 'all') as $row) {
                        $cnt++;
                        $bid    = intval($row['blog_id']);
                        $right  = ($cnt * 2) + $pos;
                        $left   = $right - 1;

                        $SQL    = SQL::newUpdate('blog');
                        $SQL->addUpdate('blog_left', $left);
                        $SQL->addUpdate('blog_right', $right);
                        $SQL->addUpdate('blog_sort', $cnt);
                        $SQL->addWhereOpr('blog_id', $bid);
                        $DB->query($SQL->get(dsn()), 'exec');

                        array_push($que, $bid);
                    }

                    if (!empty($aryPid)) {
                        $SQL    = SQL::newUpdate('blog');
                        $SQL->setUpdate('blog_left', SQL::newOpr('blog_left', ($cnt * 2), '+'));
                        $SQL->addWhereOpr('blog_left', $pos, '>');
                        $SQL->addWhereIn('blog_parent', $aryPid);
                        $DB->query($SQL->get(dsn()), 'exec');

                        $SQL    = SQL::newUpdate('blog');
                        $SQL->setUpdate('blog_right', SQL::newOpr('blog_right', ($cnt * 2), '+'));
                        $SQL->addWhereOpr('blog_right', $pos, '>=');
                        $SQL->addWhereIn('blog_parent', $aryPid);
                        $DB->query($SQL->get(dsn()), 'exec');
                    }
                    array_push($aryPid, $pid);
                }
                if (empty($que)) {
                    break;
                }
            }
            AcmsLogger::info('ブログの親子構造を修復しました');
        }
        Cache::flush('temp');

        return $this->Post;
    }
}
