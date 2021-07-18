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
        // php5.6以上で、instagramもfacebookも同じ判定
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
            $container->singleton('instagram-login', function() {
                $config = Config::loadBlogConfigSet(BID);
                return new Facebook(
                  $config->get('instagram_graph_client_id'),
                  $config->get('instagram_graph_client_secret'),
                  'v7.0',
                  'instagram'
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
