<?php

namespace Acms\Services\Storage;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;
use App;
use Config;

class StorageServiceProvider extends ServiceProvider
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
        $container->singleton('storage', function () {
            $filesystem = new Filesystem();

            if (defined('CHMOD_DIR')) {
                $filesystem->setDirectoryMod(CHMOD_DIR);
            }
            if (defined('CHMOD_FILE')) {
                $filesystem->setFileMod(CHMOD_FILE);
            }
            return $filesystem;
        });
    }
}
