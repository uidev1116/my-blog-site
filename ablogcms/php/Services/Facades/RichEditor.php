<?php

namespace Acms\Services\Facades;

/**
 * @method static string render(string $value) リッチエディタの内容をレンダリング
 * @method static string renderTitle(string $value) リッチエディタのタイトルをレンダリング
 * @method static array getAttributeMap(array $attributes, array $values) 属性マップを取得
 * @method static string getTagFromAttributeMap(array $map) 属性マップからタグを生成
 * @method static string fix(string $value) リッチエディタの内容を修正
 */
class RichEditor extends Facade
{
    protected static $instance;

    /**
     * @return string
     */
    protected static function getServiceAlias()
    {
        return 'rich-editor';
    }

    /**
     * @return bool
     */
    protected static function isCache()
    {
        return true;
    }
}
