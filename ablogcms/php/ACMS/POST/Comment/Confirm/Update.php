<?php

class ACMS_POST_Comment_Confirm_Update extends ACMS_POST_Comment
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
        $Comment =& $this->extractComment();
        if (!$this->validatePassword($Comment)) {
            return false;
        }

        $this->Post->set('action', 'update');
        $this->Post->set('step', $this->Post->isValidAll() ?
            $this->Post->get('nextstep') : $this->Post->get('step'));
        return $this->Post;
    }
}
