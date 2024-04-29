<?php

class ACMS_POST_Form_Apply extends ACMS_POST_Form
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
        $this->extract('field');
        $this->Post->set('step', 'reapply');
        return $this->Post;
    }
}
