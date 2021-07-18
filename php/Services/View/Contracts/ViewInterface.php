<?php

namespace Acms\Services\View\Contracts;

interface ViewInterface
{
    /**
     * テンプレートの初期化
     *
     * @param string $txt
     * @param ACMS_Corrector $Corrector
     *
     * @return bool
     */
    public function init($txt, $Corrector=null);

    /**
     * テンプレートを文字列で取得する
     *
     * @return string
     */
    public function get();

    /**
     * テンプレートを組み立て文字列で取得する
     *
     * @param mixed $vars
     *
     * @return string
     */
    public function render($vars);

    /**
     * ブロック・変数を追加する
     *
     * @param array|null $blocks
     * @param array $vars
     *
     * @return bool
     */
    public function add($blocks = array(), $vars = array());
}