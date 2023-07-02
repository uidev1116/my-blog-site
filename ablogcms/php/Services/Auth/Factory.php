<?php

namespace Acms\Services\Auth;

use Acms\Contracts\Factory as BaseFactory;
use App;
use DB;
use SQL;

class Factory extends BaseFactory
{
    /**
     * Factory
     *
     * @return mixed
     */
    public function createInstance()
    {
        if ( $this->isRoleAvailableUser() ) {
            return App::make('auth.role');
        } else if ( SUID && config('subscriber_view_mode') === 'on' ) {
            $app = App::getInstance();
            $Q =& $app->getQueryParameter();
            $admin = $Q->get('admin');
            if ( empty($admin) ) {
                return new SimulateSubscriber;
            }
        }
        return App::make('auth.general');
    }

    /**
     * ロールによる権限チェッックを行うユーザーか
     *
     * @param int $uid
     * @return bool
     */
    protected function isRoleAvailableUser($uid=SUID)
    {
        return isRoleAvailableUser($uid);
    }
}