<?php

class ACMS_POST_Comment_Auth extends ACMS_POST_Comment
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
     * @inheritDoc
     */
    public function post()
    {
        $Comment = $this->extract('comment');
        $Comment->setMethod('pass', 'auth');
        $Comment->validate(new ACMS_Validator_Comment());

        if ($this->Post->isValidAll()) {
            $row    = ACMS_RAM::comment(CMID);
            $Comment->setField('name', $row['comment_name']);
            $Comment->setField('mail', $row['comment_mail']);
            $Comment->setField('url', $row['comment_url']);
            $Comment->setField('title', $row['comment_title']);
            $Comment->setField('body', $row['comment_body']);
            $Comment->setField('pass', $row['comment_pass']);
            $Comment->setField('old_pass', $Comment->get('pass'));
            $this->Post->set('action', 'update');
            $this->Post->set('step', 'reapply');
        } else {
            $this->Post->set('action', 'auth');
            $this->Post->set('step', 'auth');
        }

        return $this->Post;
    }
}
