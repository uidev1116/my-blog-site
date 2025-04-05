<?php

namespace Acms\Services\Facades;

/**
 * @method static \Acms\Services\View\Contracts\ViewInterface init(string $txt, \ACMS_Corrector $Corrector = null) テンプレートの初期化
 * @method static string get() テンプレートを文字列で取得
 * @method static string render(array|object $vars) テンプレートを組み立て文字列で取得
 * @method static void add(string[]|string|null $blocks, array $vars = []) ブロックと変数を追加
 */
class View extends Facade
{
    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'view';
    }
}
