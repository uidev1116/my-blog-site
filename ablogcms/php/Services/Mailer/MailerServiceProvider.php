<?php

namespace Acms\Services\Mailer;

use App;
use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;

class MailerServiceProvider extends ServiceProvider
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
        $container->bind('mailer', 'Acms\Services\Mailer\Engine');
        $container->bind('mailer.google.smtp.api', 'Acms\Services\Mailer\Transport\GoogleApi');
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
