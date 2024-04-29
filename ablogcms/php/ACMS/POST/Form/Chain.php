<?php

class ACMS_POST_Form_Chain extends ACMS_POST_Form
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
        $this->Post->reset(true);
        return $this->Post;
    }
}
