<?php

namespace Acms\Plugins\SamplePlugin;

class Corrector
{
    /**
     * sample
     * 校正オプションのサンプルメソッド
     *
     * @param  string $txt  - 校正オプションが適用されている文字列
     * @param  array  $args - 校正オプションの引数　{var}[sample('ここの値')]
     * @return string       - 校正後の文字列
     */
    public function sample($txt, $args = array())
    {
        // 例 {var}[sample('hoge','fuga')]
        // {var}の中は，'a-blogcms' とする

        $hoge = isset($args[0]) ? $args[0] : null; // 'hoge'
        $fuga = isset($args[1]) ? $args[1] : null; // 'fuga'

        return $hoge.$fuga.'+'.$txt; // 'hogefuga+a-blog cms'
    }
}