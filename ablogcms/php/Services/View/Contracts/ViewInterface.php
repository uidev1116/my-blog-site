<?php

namespace Acms\Services\View\Contracts;

interface ViewInterface
{
    /**
     * テンプレートの初期化
     *
     * @param string $txt
     * @param \ACMS_Corrector $Corrector
     *
     * @return self
     */
    public function init($txt, $Corrector = null);

    /**
     * テンプレートを文字列で取得する
     *
     * @return string
     */
    public function get();

    /**
     * テンプレートを組み立て文字列で取得する
     *
     * @param object|array $vars
     *
     * @return string
     */
    public function render($vars);

    /**
     * ブロック・変数を追加する
     *
     * @param string[]|string|null $blocks
     * @param array $vars
     *
     * @return false|void
     */
    public function add($blocks = [], $vars = []);
}
