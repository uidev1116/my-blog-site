<?php

namespace Acms\Services\Process;

use AsyncPHP\Doorman\Manager\ProcessManager;
use AsyncPHP\Doorman\Task\ProcessCallbackTask;

class Engine
{
    /**
     * @var \AsyncPHP\Doorman\Manager\ProcessManager
     */
    protected $processManager;

    /**
     * Engine constructor.
     *
     * @param \AsyncPHP\Doorman\Manager\ProcessManager $manager
     */
    public function __construct($manager)
    {
        $this->setManager($manager);
    }

    /**
     * @param /AsyncPHP\Doorman\Manager\ProcessManager $manager
     */
    public function setManager($manager)
    {
        $this->processManager = $manager;
    }

    /**
     * @return \AsyncPHP\Doorman\Manager\ProcessManager
     */
    public function getManager()
    {
        return $this->processManager;
    }

    /**
     * @param \Closure $closure
     * @param bool $singleton
     */
    public function addTask($closure, $singleton=false)
    {
        ini_set('unserialize_callback_func', 'proxymissing');
        $rootPath = SCRIPT_DIR;
        $dirOffset = DIR_OFFSET;
        $httpPort = HTTP_PORT;
        $bid = BID;
        $taskClass = $singleton ? 'Acms\Services\Process\SingletonTask' : 'AsyncPHP\Doorman\Task\ProcessCallbackTask';
        $task = new $taskClass(function () use($closure, $rootPath, $dirOffset, $httpPort, $bid) {
            define('REQUEST_TIME', time());
            define('START_TIME', microtime(true));

            set_time_limit(0);

            /**
             * config.server.php
             */
            if ( !is_file($rootPath . '/config.server.php') ) {
                die('config.server.php is missing');
            }
            require_once $rootPath . '/config.server.php';
            require_once $rootPath . '/php/config/app.php';
            require_once $rootPath . '/php/config/polyfill.php';

            /**
             * path
             */
            setPath($rootPath . 'index.php', '/' . $dirOffset . 'index.php');

            /**
             * autoload
             */
            require_once LIB_DIR . 'vendor/autoload.php';
            spl_autoload_register('autoload');
            ini_set('unserialize_callback_func', 'autoload');

            try {
                /**
                 * Application env
                 */
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_SERVER['HTTP_HOST'] = null;
                $_SERVER['REQUEST_URI'] = null;
                $_SERVER['QUERY_STRING'] = null;
                define('HTTP_PORT', $httpPort);
                define('BID', $bid);
                define('DATE', null);
                define('IS_DEVELOPMENT', null);
                define('SEARCH_ENGINE_KEYWORD', null);
                define('SUID', null);
                define('SBID', null);
                define('EID', null);
                define('CID', null);
                define('UID', null);
                define('VIEW', null);
                define('AID', null);
                define('SESSION_NEXT_ID', null);
                define('RBID', null);
                define('SESSION_USE_COOKIE', null);
                define('TBID', null);
                define('CMID', null);
                define('UTID', null);
                define('ACMS_SID', null);
                define('RID', null);
                define('ADMIN', null);
                define('ORDER', null);
                define('PAGE', null);
                define('END', null);
                define('START', null);
                define('FIELD', null);
                define('TAG', null);
                define('KEYWORD', null);
                define('RVID', null);
                define('LICENSE_OPTION_OEM', null);
                define('SYSTEM_GENERATED_DATETIME', null);

                /**
                 * application
                 */
                $config = appConfig();
                $acms_application = new \Acms\Application();
                $acms_application->init($config['aliases'], $config['providers']);

                $closure();
            } catch ( \Exception $e ) {
                userErrorLog($e->getMessage());
            }
        });
        $this->processManager->addTask($task);
    }

    /**
     *
     */
    public function run()
    {
        $this->processManager->tick();
        return $this;
    }

    /**
     * @param \Closure $closure
     */
    public function then($closure)
    {
        while ( $this->processManager->tick() ) {
            usleep(250);
        }
        $closure();
    }

}