<?php

class ACMS_POST_Comment_Confirm_Delete extends ACMS_POST_Comment
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
     * @return false|Field
     */
    function post()
    {
        $Comment = $this->extract('comment');
        $Comment->reset('name');
        $Comment->reset('mail');
        $Comment->reset('url');
        $Comment->reset('title');
        $Comment->reset('body');

        $Comment->setField('name', ACMS_RAM::commentName(CMID));
        $Comment->setField('mail', ACMS_RAM::commentMail(CMID));
        $Comment->setField('url', ACMS_RAM::commentUrl(CMID));
        $Comment->setField('title', ACMS_RAM::commentTitle(CMID));
        $Comment->setField('body', ACMS_RAM::commentBody(CMID));

        if ( !$this->validatePassword($Comment) ) {
            return false;
        }
        $this->Post->set('action', 'delete');
        $this->Post->set('step', $this->Post->isValidAll() ?
            $this->Post->get('nextstep') : $this->Post->get('step')
        );
        return $this->Post;
    }
}
