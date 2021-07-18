<?php

define('REQUEST_TIME', time());
define('START_TIME', microtime(true));
define('ACTIVATION_ENDPOINT', 'https://mypage.a-blogcms.jp/api/activation');

/**
 * config.server.php
 */
if ( !is_file('config.server.php') ) {
    die('config.server.php is missing');
}
require_once 'config.server.php';
require_once PHP_DIR . 'config/app.php';
require_once PHP_DIR . 'config/polyfill.php';
if (file_exists('config.user.php')) {
    require_once 'config.user.php';
}

/**
 * path
 */
setPath($_SERVER['SCRIPT_FILENAME']);

/**
 * autoload
 */
require_once LIB_DIR . 'vendor/autoload.php';
spl_autoload_register('autoload');

try {
    /**
     * application
     */
    $config = appConfig();
    $acms_application = new Acms\Application();
    $acms_application->init($config['aliases'], $config['providers']);

    /**
     * shutdown
     */
    register_shutdown_function('shutdown');

    /**
     * load license
     */
    $acms_application->loadLicense();

    /**
     * setup
     */
    if ( is_file(SCRIPT_DIR . 'setup/index.php') ) {
        die(header('Location: ' . BASE_URL . 'setup/index.php'));
    }

    libxml_disable_entity_loader(true);
    require_once LIB_DIR . 'main.php';

    $acms_application->checkException();
} catch ( Exception $e ) {
    $acms_application->showError($e);
}
