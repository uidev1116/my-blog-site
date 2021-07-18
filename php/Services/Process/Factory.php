<?php

namespace Acms\Services\Process;

use AsyncPHP\Doorman\Manager\ProcessManager;

class Factory
{
    /**
     * @var string
     */
    protected $workerPath;

    /**
     * @var string
     */
    protected $logPath;

    public function __construct($worker, $log)
    {
        $this->workerPath = $worker;
        $this->logPath = $log;
    }

    /**
     * Factory
     *
     * @return mixed
     */
    public function newProcessManager()
    {
        $manager = new ProcessManager();
        $manager->setWorker($this->workerPath);
        if (defined('PHP_PROCESS_BINARY') && PHP_PROCESS_BINARY) {
            $manager->setBinary(PHP_PROCESS_BINARY);
        }
        if ( !empty($this->logPath) ) {
            $manager->setLogPath($this->logPath);
        }
        return new Engine($manager);
    }
}