<?php

class ACMS_POST_Comment_Apply extends ACMS_POST_Comment
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
        $Comment =& $this->extractComment();
        $this->Post->set('step', 'reapply');
        return $this->Post;
    }
}
