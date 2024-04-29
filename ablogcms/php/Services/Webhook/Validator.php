<?php

namespace Acms\Services\Webhook;

use Acms\Services\Facades\Application;

class Validator
{
    /**
     * URLのスキーマが http or https か確認する
     *
     * @param string|null $val - その変数の値
     * @param string|null $arg - <input type="hidden" name="var:v#sample" value="ここの値">
     * @return bool - 入力が正しい場合は "ture" そうでない場合は "false" を返す
     */
    public function webhookScheme($val, $arg): bool
    {
        $webhook = Application::make('webhook');

        if ($val === '' || is_null($val)) {
            return true;
        }
        return $webhook->validateUrlScheme($val);
    }

    /**
     * URLのホストがホワイトリストに含まれるか確認
     *
     * @param string|null $val - その変数の値
     * @param string|null $arg - <input type="hidden" name="var:v#sample" value="ここの値">
     * @return bool - 入力が正しい場合は "ture" そうでない場合は "false" を返す
     */
    public function webhookWhitelist($val, $arg): bool
    {
        $webhook = Application::make('webhook');

        if ($val === '' || is_null($val)) {
            return true;
        }
        return $webhook->validateUrlWhiteList($val);
    }
}
