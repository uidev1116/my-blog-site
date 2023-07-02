<?php

/**
 * App Abstract Class
 */
abstract class ACMS_App
{
    /**
     * アプリのバージョン
     * @var string|int $version
     */
    public $version;

    /**
     * アプリの名前
     * @var string $name
     */
    public $name;

    /**
     * アプリの提供者
     * @var string $author
     */
    public $author;

    /**
     * アプリの説明
     * @var string $desc
     */
    public $desc;

    /**
     * インストール
     * @abstract
     */
    abstract public function install();

    /**
     * アンインストール
     * @abstract
     */
    abstract public function uninstall();

    /**
     * 有効化
     * @abstract
     */
    abstract public function activate();

    /**
     * 無効化
     * @abstract
     */
    abstract public function deactivate();

    /**
     * アップデート
     * @abstract
     */
    abstract public function update();

    /**
     * バリデーション
     * @abstract
     */
    abstract public function checkRequirements();
}