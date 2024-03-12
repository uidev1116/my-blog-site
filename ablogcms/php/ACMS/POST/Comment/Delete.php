<?php

class ACMS_POST_Comment_Delete extends ACMS_POST_Comment
{
    function post()
    {
        if (!CMID) {
            die();
        }
        $DB = DB::singleton(dsn());

        if (!$this->validatePassword()) {
            return false;
        }

        $step       = $this->Post->get('step');
        $nextstep   = $this->Post->get('nextstep');
        $redirect   = $this->Post->get('redirect');

        $l  = ACMS_RAM::commentLeft(CMID);
        $r  = ACMS_RAM::commentRight(CMID);
        $gap = $r - $l + 1;

        $SQL    = SQL::newDelete('comment');
        $SQL->addWhereOpr('comment_left', $l, '>=');
        $SQL->addWhereOpr('comment_right', $r, '<=');
        $SQL->addWhereOpr('comment_entry_id', EID);
        $SQL->addWhereOpr('comment_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL    = SQL::newUpdate('comment');
        $SQL->setUpdate('comment_left', SQL::newOpr('comment_left', $gap, '-'));
        $SQL->addWhereOpr('comment_left', $r, '>');
        $SQL->addWhereOpr('comment_entry_id', EID);
        $SQL->addWhereOpr('comment_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL    = SQL::newUpdate('comment');
        $SQL->setUpdate('comment_right', SQL::newOpr('comment_right', $gap, '-'));
        $SQL->addWhereOpr('comment_right', $r, '>');
        $SQL->addWhereOpr('comment_entry_id', EID);
        $SQL->addWhereOpr('comment_blog_id', BID);
        $DB->query($SQL->get(dsn()), 'exec');

        AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーのコメントを削除しました', [
            'comment_id' => CMID,
        ]);

        if (!empty($redirect) && Common::isSafeUrl($redirect)) {
            $this->redirect($redirect);
        } elseif (!empty($nextstep)) {
            $this->Post->set('step', $nextstep);
            $this->Post->set('action', 'delete');
            return $this->Post;
        } else {
            return true;
        }
    }
}
