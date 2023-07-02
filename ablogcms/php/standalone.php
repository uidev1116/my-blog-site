<?php

define('REQUEST_TIME', time());
define('START_TIME', microtime(true));

/**
 * load
 */
require_once dirname(__FILE__) . '/../config.server.php';
require_once dirname(__FILE__) . '/../php/config/app.php';
require_once dirname(__FILE__) . '/../php/config/polyfill.php';
setPath(realpath(dirname(__FILE__) . '/../index.php'));

/**
 * autoload
 */
require_once LIB_DIR . 'vendor/autoload.php';
spl_autoload_register('autoload');

/**
 * .env
 */
if (file_exists(SCRIPT_DIR . '.env')) {
    Dotenv\Dotenv::createImmutable(SCRIPT_DIR)->load();
}

function env($key, $default = '')
{
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}

/**
 * Application env
 */
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = null;
}
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = null;
}
if (!isset($_SERVER['QUERY_STRING'])) {
    $_SERVER['QUERY_STRING'] = null;
}
if (!defined('BID')) {
    define('BID', 1);
}
if (!defined('DATE')) {
    define('DATE', null);
}
if (!defined('IS_DEVELOPMENT')) {
    define('IS_DEVELOPMENT', null);
}
if (!defined('SEARCH_ENGINE_KEYWORD')) {
    define('SEARCH_ENGINE_KEYWORD', null);
}
if (!defined('SUID')) {
    define('SUID', null);
}
if (!defined('SBID')) {
    define('SBID', null);
}
if (!defined('EID')) {
    define('EID', null);
}
if (!defined('CID')) {
    define('CID', null);
}
if (!defined('UID')) {
    define('UID', null);
}
if (!defined('VIEW')) {
    define('VIEW', null);
}
if (!defined('AID')) {
    define('AID', null);
}
if (!defined('SESSION_NEXT_ID')) {
    define('SESSION_NEXT_ID', null);
}
if (!defined('RBID')) {
    define('RBID', null);
}
if (!defined('SESSION_USE_COOKIE')) {
    define('SESSION_USE_COOKIE', null);
}
if (!defined('TBID')) {
    define('TBID', null);
}
if (!defined('CMID')) {
    define('CMID', null);
}
if (!defined('UTID')) {
    define('UTID', null);
}
if (!defined('ACMS_SID')) {
    define('ACMS_SID', null);
}
if (!defined('RID')) {
    define('RID', null);
}
if (!defined('ADMIN')) {
    define('ADMIN', null);
}
if (!defined('ORDER')) {
    define('ORDER', null);
}
if (!defined('PAGE')) {
    define('PAGE', null);
}
if (!defined('END')) {
    define('END', null);
}
if (!defined('START')) {
    define('START', null);
}
if (!defined('FIELD')) {
    define('FIELD', null);
}
if (!defined('TAG')) {
    define('TAG', null);
}
if (!defined('KEYWORD')) {
    define('KEYWORD', null);
}
if (!defined('RVID')) {
    define('RVID', null);
}
if (!defined('LICENSE_OPTION_OEM')) {
    define('LICENSE_OPTION_OEM', null);
}
if (!defined('SYSTEM_GENERATED_DATETIME')) {
    define('SYSTEM_GENERATED_DATETIME', null);
}

function acmsStandAloneRun($exec)
{
    /**
     * Application
     */
    $config = appConfig();
    $acmsApplication = new Acms\Application();
    $acmsApplication->init($config['aliases'], $config['providers']);

    try {
        /**
         * 処理実行
         */
        $res = call_user_func($exec);
        $acmsApplication->checkException();

        /**
         * 終了コード
         */
        if (!$res) {
            exit(1);
        }
        exit(0);
    } catch (Exception $e) {
        $date = date('Y-m-d H:i:s');
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, "$date [Error] " . $e->getMessage() . "\n");
        fclose($stderr);
        exit(1);
    }
}

function acmsStdMessage($message)
{
    $date = date('Y-m-d H:i:s');
    $stdout = fopen('php://stdout', 'w');
    fwrite($stdout, "$date $message\n");
    fclose($stdout);
}
