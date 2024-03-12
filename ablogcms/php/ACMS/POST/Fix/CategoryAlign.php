<?php

class ACMS_POST_Fix_CategoryAlign extends ACMS_POST
{
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('align', 'operable', (1
            and sessionWithAdministration()
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
                    $SQL    = SQL::newSelect('category');
                    $SQL->addWhereOpr('category_id', $pid);
                    $SQL->addWhereOpr('category_blog_id', BID);
                    if (!!($row = $DB->query($SQL->get(dsn()), 'row'))) {
                        $pos    = intval($row['category_left']);// - 1;
                    } else {
                        continue;
                    }
                }

                $SQL    = SQL::newSelect('category');
                $SQL->setSelect('category_id');
                $SQL->addWhereOpr('category_parent', $pid);
                $SQL->addWhereOpr('category_blog_id', BID);
                $SQL->setOrder('category_sort');

                $cnt    = 0;
                if (!!($all = $DB->query($SQL->get(dsn()), 'all'))) {
                    foreach ($DB->query($SQL->get(dsn()), 'all') as $row) {
                        $cnt++;
                        $cid    = intval($row['category_id']);
                        $right  = ($cnt * 2) + $pos;
                        $left   = $right - 1;

                        $SQL    = SQL::newUpdate('category');
                        $SQL->addUpdate('category_left', $left);
                        $SQL->addUpdate('category_right', $right);
                        $SQL->addUpdate('category_sort', $cnt);
                        $SQL->addWhereOpr('category_id', $cid);
                        $SQL->addWhereOpr('category_blog_id', BID);
                        $DB->query($SQL->get(dsn()), 'exec');

                        array_push($que, $cid);
                    }

                    if (!empty($aryPid)) {
                        $SQL    = SQL::newUpdate('category');
                        $SQL->setUpdate('category_left', SQL::newOpr('category_left', ($cnt * 2), '+'));
                        $SQL->addWhereOpr('category_left', $pos, '>');
                        $SQL->addWhereIn('category_parent', $aryPid);
                        $SQL->addWhereOpr('category_blog_id', BID);
                        $DB->query($SQL->get(dsn()), 'exec');

                        $SQL    = SQL::newUpdate('category');
                        $SQL->setUpdate('category_right', SQL::newOpr('category_right', ($cnt * 2), '+'));
                        $SQL->addWhereOpr('category_right', $pos, '>=');
                        $SQL->addWhereIn('category_parent', $aryPid);
                        $SQL->addWhereOpr('category_blog_id', BID);
                        $DB->query($SQL->get(dsn()), 'exec');
                    }
                    array_push($aryPid, $pid);
                }

                if (empty($que)) {
                    break;
                }
            }
            AcmsLogger::info('カテゴリーの親子構造を修復しました');
        }
        Cache::flush('temp');

        return $this->Post;
    }
}
