<?php

namespace Acms\Custom;

/**
 * バリデーターにユーザー定義のメソッドを追加します
 * ユーザー定義のメソッドが優先されます。
 */
class Validator
{
    /**
     * sample
     * バリデーターのサンプルメソッド
     *
     * @param  string $val - その変数の値
     * @param  string $arg - <input type="hidden" name="var:v#sample" value="ここの値">
     * @return boolean     - 入力が正しい場合は "ture" そうでない場合は "false" を返す
     */
    function sample($val, $arg)
    {
        /** 
        * 例:
        * <input type="text" name="var" value="{var}">
        * <input type="hidden" name="field[]" value="var"> 
        * <input type="hidden" name="var:v#sample" value="cms">
        *
        * <!-- BEGIN var:validator#sample -->
        *   <p class="acms-admin-text-error">cmsという文字列を含めてください。</p>
        * <!-- END var:validator#sample -->
        *
        * {var}の中は，'a-blogcms' とする
        */

        // name="var:v#sample" value="cms" で指定した
        // 文字列が含まれていなかったらエラーを出す
        return (strpos($val, $arg) !== false);
    }
}
