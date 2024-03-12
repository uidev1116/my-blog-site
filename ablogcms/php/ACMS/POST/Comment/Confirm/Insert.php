<?php

class ACMS_POST_Comment_Confirm_Insert extends ACMS_POST_Comment
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
        $Comment    =& $this->extractComment();
        $this->Post->set('action', 'insert');
        $this->Post->set('step', $this->Post->isValidAll() ?
            $this->Post->get('nextstep') : $this->Post->get('step'));
        return $this->Post;
    }
}
