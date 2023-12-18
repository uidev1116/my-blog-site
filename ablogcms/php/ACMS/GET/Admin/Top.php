<?php

use Acms\Services\Update\System\CheckForUpdate;

class ACMS_GET_Admin_Top extends ACMS_GET_Admin
{
    function get()
    {
        if ( 'top' <> ADMIN ) return false;
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $checkUpdateService = App::make('update.check');

        if (sessionWithContribution() && IS_LICENSED) {
            $Tpl->add('insert', array('cid' => CID));
            foreach (configArray('ping_weblog_updates_endpoint') as $val) {
                $Tpl->add('ping_weblog_updates_endpoint:loop', array(
                    'ping_weblog_updates_endpoint'  => $val,
                ));
            }
            foreach (configArray('ping_weblog_updates_extended_endpoint') as $val) {
                $Tpl->add('ping_weblog_updates_extended_endpoint:loop', array(
                    'ping_weblog_updates_extended_endpoint' => $val,
                ));
            }
        }

        if (1
            && sessionWithAdministration()
            && config('system_update_range') !== 'none'
            && RBID === BID
            && SBID === BID
            && ($checkUpdateService->getFinalCheckTime() + 60 * 60) < REQUEST_TIME
        ) {
            try {
                $checkUpdateService->check(phpversion(), CheckForUpdate::PATCH_VERSION);
            } catch (\Exception $e) {}
        }

        return $Tpl->get();
    }
}
