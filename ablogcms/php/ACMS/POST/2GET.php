<?php

use Acms\Services\Facades\Common;

class ACMS_POST_2GET extends ACMS_POST
{
    public $isCacheDelete  = false;

    protected $isCSRF = false;

    function post()
    {
        $post = new Field($this->Post);
        if ($post->get('nocache') === 'yes') {
            $post->add('query', 'nocache');
        }
        $this->executeRedirect($post); // @phpstan-ignore-line
    }

    /**
     * リダイレクト実行
     * @param Field $post
     * @return void
     */
    protected function executeRedirect(Field $post): void
    {
        $this->redirect(acmsLink(Common::getUriObject($post), true, true, false, false));
    }
}
