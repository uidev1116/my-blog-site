<?php

namespace Acms\Services\Webhook;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;
use Acms\Services\Common\HookFactory;

class WebhookServiceProvider extends ServiceProvider
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
        $container->bind('webhook', function () {
            $payload = new Payload;
            return new Engine($payload);
        });
        $hook = HookFactory::singleton();
        $hook->attach('Webhook', new Hook);
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
