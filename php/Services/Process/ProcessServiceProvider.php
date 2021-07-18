<?php

namespace Acms\Services\Process;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;
use Acms\Services\Facades\Storage;

class ProcessServiceProvider extends ServiceProvider
{
    /**
     * register service
     *
     * @param \Acms\Services\Container $container
     *
     * @return void
     */
    public function register(Container $container)
    {
        $container->singleton('process', function () {
            $workerPath = SCRIPT_DIR . 'php/Services/Process/bin/worker.php';
            $logPath = false;
            if ( 1
                && defined('ASYNC_PROCESS_LOG_PATH')
                && ASYNC_PROCESS_LOG_PATH
                && Storage::isDirectory(ASYNC_PROCESS_LOG_PATH)
            ) {
                $logPath = ASYNC_PROCESS_LOG_PATH;
            }
            return new Factory($workerPath, $logPath);
        });
    }

    /**
     * initialize service
     *
     * @return void
     */
    public function init()
    {

    }
}