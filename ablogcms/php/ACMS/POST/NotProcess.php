<?php

class ACMS_POST_NotProcess extends ACMS_POST
{
    /**
     * @var bool
     */
    public $isCacheDelete = false;

    /**
     * @var bool
     */
    protected $isCSRF = false;

    /**
     * Main
     * @inheritDoc
     */
    public function post()
    {
        return $this->Post;
    }
}
