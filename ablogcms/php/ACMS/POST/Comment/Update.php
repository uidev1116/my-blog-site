<?php

class ACMS_POST_Comment_Update extends ACMS_POST_Comment
{
    /**
     * CSRF対策
     *
     * @var bool
     */
    protected $isCSRF = true;

    /**
     * 二重送信対策
     *
     * @var bool
     */
    protected $checkDoubleSubmit = true;

    /**
     * @return bool|Field
     */
    function post()
    {
        $nextstep   = $this->Post->get('nextstep');
        $redirect   = $this->Post->get('redirect');

        $Comment = $this->extract('comment');
        $Comment->setMethod('old_pass', 'passCheck');
        $Comment->setMethod('@cmid_is_null', 'through', !!CMID);
        $Comment->validate(new ACMS_Validator_Comment());

        if ( !$Comment->isValid() ) {
            $this->Post->set('action', 'update');
            return $this->Post;
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newUpdate('comment');
        $SQL->addUpdate('comment_host', REMOTE_ADDR);
//        $SQL->addUpdate('comment_user_id', intval(SUID));
        $SQL->addUpdate('comment_title', $Comment->get('title'));
        $SQL->addUpdate('comment_body', $Comment->get('body'));
        $SQL->addUpdate('comment_name', $Comment->get('name'));
        $SQL->addUpdate('comment_mail', strval($Comment->get('mail')));
        $SQL->addUpdate('comment_url', strval($Comment->get('url')));
        $SQL->addUpdate('comment_pass', $Comment->get('pass'));
        $SQL->addWhereOpr('comment_id', CMID);
        $DB->query($SQL->get(dsn()), 'exec');

        AcmsLogger::info('「' . ACMS_RAM::entryTitle(EID) . '」エントリーのコメントを更新しました', [
            'comment_id' => CMID,
        ]);

        if (!empty($redirect) && Common::isSafeUrl($redirect)) {
            $this->redirect($redirect);
        } else if ( !empty($nextstep) ) {
            $this->Post->set('step', $nextstep);
            $this->Post->set('action', 'update');
            return $this->Post;
        } else {
            return true;
        }
    }
}
