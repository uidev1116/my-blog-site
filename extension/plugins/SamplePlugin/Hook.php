<?php

namespace Acms\Plugins\SamplePlugin;

class Hook
{
    /**
     * 例: グローバル変数の拡張
     *
     * @param array &$globalVars
     */
    public function extendsGlobalVars(&$globalVars)
    {
//         $globalVars->set('key', 'value');
    }

    /**
     * キャッシュルールに特殊ルールを追加
     *
     * @param string $customRuleString
     */
    public function addCacheRule(&$customRuleString)
    {
//        $customRuleString = UA_GROUP; // デバイスによってルールを分ける場合
    }
}
