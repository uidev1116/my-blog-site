<?php

namespace Acms\Services\Webhook;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;
use Acms\Services\Common\HookFactory;
use Acms\Services\Common\ValidatorFactory;

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
        $whiteList = [];
        $whiteListStr = env('WEBHOOK_WHITE_LIST', '');
        if ($whiteListStr) {
            $whiteList = explode(',', $whiteListStr);
            $whiteList = array_map(function ($item) {
                return trim($item);
            }, $whiteList);
        }

        $container->bind('webhook', function () use ($whiteList) {
            $payload = new Payload();
            return new Engine($payload, $whiteList);
        });
        $hook = HookFactory::singleton();
        $hook->attach('Webhook', new Hook());

        $validator = ValidatorFactory::singleton();
        $validator->attach('WebhookValidator', new Validator());
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
