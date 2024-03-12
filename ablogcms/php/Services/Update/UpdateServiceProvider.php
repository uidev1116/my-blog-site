<?php

namespace Acms\Services\Update;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class UpdateServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $repositoryCache;

    /**
     * @var string
     */
    protected $repositorySchema;

    /**
     * register service
     *
     * @param \Acms\Services\Container $container
     *
     * @return void
     */
    public function register(Container $container)
    {
        $cache = SCRIPT_DIR . 'cache/update.json';
        $schema = LIB_DIR . 'Services/Update/template/schema.json';
        $this->repositoryCache = $cache;
        $this->repositorySchema = $schema;

        $container->singleton('update.logger', function () {
            return new Logger(CACHE_DIR . 'update-process.json');
        });
        $container->singleton('update.check', function () use ($cache, $schema) {
            return new System\CheckForUpdate(config('system_update_repository'), $cache, $schema);
        });
        $container->singleton('update.download', function () use ($container) {
            return new System\Download($container->make('update.logger'));
        });
        $container->singleton('update.place.file', function () use ($container) {
            return new System\PlaceFile($container->make('update.logger'));
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
