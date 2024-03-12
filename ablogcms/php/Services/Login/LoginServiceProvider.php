<?php

namespace Acms\Services\Login;

use Acms\Contracts\ServiceProvider;
use Acms\Services\Container;
use ACMS_RAM;

class LoginServiceProvider extends ServiceProvider
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
        // 管理ユーザー用
        if (!defined('LOGIN_SEGMENT')) {
            define('LOGIN_SEGMENT', 'login');
        }
        if (!defined('ADMIN_RESET_PASSWORD_SEGMENT')) {
            define('ADMIN_RESET_PASSWORD_SEGMENT', 'admin-reset-password');
        }
        if (!defined('ADMIN_RESET_PASSWORD_AUTH_SEGMENT')) {
            define('ADMIN_RESET_PASSWORD_AUTH_SEGMENT', 'admin-reset-password-auth');
        }
        if (!defined('ADMIN_TFA_RECOVERY_SEGMENT')) {
            define('ADMIN_TFA_RECOVERY_SEGMENT', 'admin-tfa-recovery');
        }

        // 一般ユーザー用
        if (!defined('SIGNIN_SEGMENT')) {
            define('SIGNIN_SEGMENT', 'signin');
        }
        if (!defined('SIGNUP_SEGMENT')) {
            define('SIGNUP_SEGMENT', 'signup');
        }
        if (!defined('RESET_PASSWORD_SEGMENT')) {
            define('RESET_PASSWORD_SEGMENT', 'reset-password');
        }
        if (!defined('RESET_PASSWORD_AUTH_SEGMENT')) {
            define('RESET_PASSWORD_AUTH_SEGMENT', 'reset-password-auth');
        }
        if (!defined('TFA_RECOVERY_SEGMENT')) {
            define('TFA_RECOVERY_SEGMENT', 'tfa-recovery');
        }

        // 管理・一般共用
        if (!defined('PROFILE_UPDATE_SEGMENT')) {
            define('PROFILE_UPDATE_SEGMENT', 'mypage/update-profile');
        }
        if (!defined('PASSWORD_UPDATE_SEGMENT')) {
            define('PASSWORD_UPDATE_SEGMENT', 'mypage/update-password');
        }
        if (!defined('EMAIL_UPDATE_SEGMENT')) {
            define('EMAIL_UPDATE_SEGMENT', 'mypage/update-email');
        }
        if (!defined('TFA_UPDATE_SEGMENT')) {
            define('TFA_UPDATE_SEGMENT', 'mypage/update-tfa');
        }
        if (!defined('WITHDRAWAL_SEGMENT')) {
            define('WITHDRAWAL_SEGMENT', 'mypage/withdrawal');
        }


        $container->singleton('login', 'Acms\Services\Login\Helper');
        $container->bind('login.tfa', function () {
            return new Tfa(ACMS_RAM::blogName(BID));
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
