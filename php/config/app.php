<?php

/**
 * application setting
 *
 * @return array
 */
function appConfig()
{
    return array(
        /**
         * ****************************************************
         * Service Providers
         * ****************************************************
         */
        'providers' => array(
            'Acms\Services\Cache\CacheServiceProvider',
            'Acms\Services\Session\SessionServiceProvider',
            'Acms\Services\Api\ApiServiceProvider',
            'Acms\Services\Database\DatabaseServiceProvider',
            'Acms\Services\Auth\AuthServiceProvider',
            'Acms\Services\Mailer\MailerServiceProvider',
            'Acms\Services\Storage\StorageServiceProvider',
            'Acms\Services\View\ViewServiceProvider',
            'Acms\Services\Template\TemplateServiceProvider',
            'Acms\Services\Approval\ApprovalServiceProvider',
            'Acms\Services\Image\ImageServiceProvider',
            'Acms\Services\Config\ConfigServiceProvider',
            'Acms\Services\Blog\BlogServiceProvider',
            'Acms\Services\Entry\EntryServiceProvider',
            'Acms\Services\Module\ModuleServiceProvider',
            'Acms\Services\Common\CommonServiceProvider',
            'Acms\Services\Http\HttpServiceProvider',
            'Acms\Services\Login\LoginServiceProvider',
            'Acms\Services\Update\UpdateServiceProvider',
            'Acms\Services\Media\MediaServiceProvider',
            'Acms\Services\Webhook\WebhookServiceProvider',
            'Acms\Services\React\ReactServiceProvider',
            'Acms\Services\StaticExport\StaticExportServiceProvider',
            'Acms\Services\Preview\PreviewServiceProvider',
            'Acms\Services\SocialLogin\SocialLoginServiceProvider',
            'Acms\Services\RichEditor\RichEditorServiceProvider',
        ),

        /**
         * ****************************************************
         * Class Aliases
         * ****************************************************
         */
        'aliases' => array(
            'App'       => 'Acms\Services\Facades\Application',
            'Cache'     => 'Acms\Services\Facades\Cache',
            'Session'   => 'Acms\Services\Facades\Session',
            'Api'       => 'Acms\Services\Facades\Api',
            'DB'        => 'Acms\Services\Facades\Database',
            'Auth'      => 'Acms\Services\Facades\Auth',
            'Mailer'    => 'Acms\Services\Facades\Mailer',
            'Storage'   => 'Acms\Services\Facades\Storage',
            'View'      => 'Acms\Services\Facades\View',
            'Tpl'       => 'Acms\Services\Facades\Template',
            'Approval'  => 'Acms\Services\Facades\Approval',
            'Image'     => 'Acms\Services\Facades\Image',
            'Config'    => 'Acms\Services\Facades\Config',
            'Blog'      => 'Acms\Services\Facades\Blog',
            'Entry'     => 'Acms\Services\Facades\Entry',
            'Module'    => 'Acms\Services\Facades\Module',
            'Common'    => 'Acms\Services\Facades\Common',
            'Http'      => 'Acms\Services\Facades\Http',
            'Login'     => 'Acms\Services\Facades\Login',
            'Tfa'       => 'Acms\Services\Facades\Tfa',
            'Media'     => 'Acms\Services\Facades\Media',
            'Preview'   => 'Acms\Services\Facades\Preview',
            'Webhook'   => 'Acms\Services\Facades\Webhook',
            'ACMS_Hook' => 'Acms\Services\Common\HookFactory',
        ),
    );
}

/**
 * autoload
 *
 * @param string $name
 */
function autoload($name)
{
    if ($name === 'ACMS_APP') $name = 'ACMS_App'; // Ver. 2.8.0 未満の対応
    $classPath = implode(DIRECTORY_SEPARATOR, explode('_', $name)) . '.php';

    $filePath = LIB_DIR . $classPath;

    if ( is_readable($filePath) ) {
        require_once $filePath;
    } else {
        // LIB_DIRから見つからなければPEAR内を再度探索
        $pearPath = LIB_DIR . 'PEAR' . DIRECTORY_SEPARATOR . $classPath;
        if ( is_readable($pearPath) ) {
            require_once $pearPath;
        }
    }
}

function setPath($script_file, $script_name = false)
{
    $script_name = $script_name ? $script_name : $_SERVER['SCRIPT_NAME'];
    define('SCRIPT_FILE', preg_match('@(.*?)([^/]+)$@', $script_file, $match) ? $script_file : die('script file is unknown'));
    define('SCRIPT_DIR', $match[1]);

    define('SCRIPT_FILENAME', $match[2]);
    define('DOCUMENT_ROOT', rtrim(substr(SCRIPT_FILE, 0, strlen(SCRIPT_FILE) - strlen($script_name)), '/') . '/');
    define('DIR_OFFSET', substr(SCRIPT_DIR, strlen(DOCUMENT_ROOT)));
    chdir(SCRIPT_DIR);

    if (!defined('REWRITE_ENABLE')) define('REWRITE_ENABLE', (isset($_SERVER['rewrite']) or REWRITE_FORCE)); // 使ってない
    if (!defined('LIB_DIR')) define('LIB_DIR', '/' == substr(PHP_DIR, 0, 1) ? PHP_DIR : SCRIPT_DIR . PHP_DIR);
    if (!defined('ACMS_LIB_DIR')) define('ACMS_LIB_DIR', LIB_DIR . 'ACMS/');
    if (!defined('AAPP_LIB_DIR')) define('AAPP_LIB_DIR', LIB_DIR . 'AAPP/');
    if (!defined('PLUGIN_DIR')) define('PLUGIN_DIR', '/extension/plugins/');
    if (!defined('PLUGIN_LIB_DIR')) define('PLUGIN_LIB_DIR',  SCRIPT_DIR . 'extension/plugins/');
    ini_set('include_path', LIB_DIR . 'PEAR/');

}
