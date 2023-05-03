<?php

namespace Acms\Services\SocialLogin;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;
use Acms\Services\Facades\Config;

class SocialLoginServiceProvider extends ServiceProvider
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
        if (isFacebookLoginAvailable()) {
            $container->singleton('facebook-login', function() {
                $config = Config::loadBlogConfigSet(BID);
                return new Facebook(
                    $config->get('facebook_app_id'),
                    $config->get('facebook_app_secret'),
                    'v3.2',
                    'facebook'
                );
            });
        }
        $container->singleton('line-login', function() {
            $config = Config::loadBlogConfigSet(BID);
            return new Line(
                $config->get('line_app_id'),
                $config->get('line_app_secret')
            );
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
