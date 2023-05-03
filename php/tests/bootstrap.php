<?php

require __DIR__ . "/../vendor/autoload.php";

spl_autoload_register(function ($name) {
    // e.g. ACMS_GET
    $classPath = implode(DIRECTORY_SEPARATOR, explode('_', $name)) . '.php';

    // e.g. Tests\Services\Update\DatabaseInfoTest
    $classPath2 = str_replace('\\', DIRECTORY_SEPARATOR, $name);
    $classPath2 = preg_replace('/^(Tests|Acms)(\/|\\\)/', '', $classPath2) . '.php';

    $filePath = __DIR__ . '/' . $classPath;
    $testPath = __DIR__ . '/' . $classPath2;
    $acmsPath = __DIR__ . '/../' . $classPath2;

    if ( is_readable($filePath) ) {
        require_once $filePath;
    } else if ( is_readable($testPath) ) {
        require_once $testPath;
    } else if ( is_readable($acmsPath) ) {
        require_once $acmsPath;
    }
});

/**
 * App
 */
$phpDir = __DIR__ . '/../';

define('REQUEST_TIME', time());
define('START_TIME', microtime(true));

require_once $phpDir . '../../../config.server.php';
require_once $phpDir . 'config/app.php';
require_once $phpDir . 'config/polyfill.php';

$path = substr(__FILE__, 0, strlen(__FILE__) - strlen('php/tests/bootstrap.php')) . 'index.php';
setPath($path, 'index.php');
spl_autoload_register('autoload');

/**
 * application
 */
$config = appConfig();
$acms_application = new Acms\Application();
$acms_application->init($config['aliases'], $config['providers']);

