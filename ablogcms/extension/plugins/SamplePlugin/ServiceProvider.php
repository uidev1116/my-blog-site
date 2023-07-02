<?php

namespace Acms\Plugins\SamplePlugin;

use ACMS_App;
use Acms\Services\Common\HookFactory;
use Acms\Services\Common\CorrectorFactory;
use Acms\Services\Common\ValidatorFactory;
use Acms\Services\Common\InjectTemplate;

class ServiceProvider extends ACMS_App
{
    /**
     * @var string
     */
    public $version = '1.0.0';

    /**
     * @var string
     */
    public $name = 'SamplePlugin';

    /**
     * @var string
     */
    public $author = 'com.appleple';

    /**
     * @var bool
     */
    public $module = false;

    /**
     * @var bool|string
     */
    public $menu = false;

    /**
     * @var string
     */
    public $desc = 'サンプルのプラグインです。';

    /**
     * サービスの初期処理
     */
    public function init()
    {
        $hook = HookFactory::singleton();
        $hook->attach('SampleHook', new Hook);

        $corrector = CorrectorFactory::singleton();
        $corrector->attach('SampleCorrector', new Corrector);

        $validator = ValidatorFactory::singleton();
        $validator->attach('SampleValidator', new Validator);

        $inject = InjectTemplate::singleton();
        $inject->add('admin-module-select', PLUGIN_DIR . 'SamplePlugin/template/module-select.html');
        $inject->add('admin-module-config-Sample', PLUGIN_DIR . 'SamplePlugin/template/config.html');
    }

    /**
     * インストールする前の環境チェック処理
     *
     * @return bool
     */
    public function checkRequirements()
    {
        return true;
    }

    /**
     * インストールするときの処理
     * データベーステーブルの初期化など
     *
     * @return void
     */
    public function install()
    {

    }

    /**
     * アンインストールするときの処理
     * データベーステーブルの始末など
     *
     * @return void
     */
    public function uninstall()
    {

    }

    /**
     * アップデートするときの処理
     *
     * @return bool
     */
    public function update()
    {
        return true;
    }

    /**
     * 有効化するときの処理
     *
     * @return bool
     */
    public function activate()
    {
        return true;
    }

    /**
     * 無効化するときの処理
     *
     * @return bool
     */
    public function deactivate()
    {
        return true;
    }
}
