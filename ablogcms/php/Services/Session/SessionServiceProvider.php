<?php

namespace Acms\Services\Session;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class SessionServiceProvider extends ServiceProvider
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
        $config = [
            'path' => '/',
            'domain' => getCookieHost(),
            'secure' => COOKIE_SECURE === 1,
            'lifetime' => intval(env('SESSION_COOKIE_LIFETIME', '259200')),
            'httpOnly' => COOKIE_HTTPONLY === 1,
            'sameSite' => defined('COOKIE_SAME_SITE') ? COOKIE_SAME_SITE : 'Lax',
        ];
        $container->singleton('session', function () use ($config) {
            return new Engine(SESSION_NAME, $config);
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
