<?php

use Acms\Services\Update\System\CheckForUpdate;

class ACMS_GET_Admin_Top extends ACMS_GET_Admin
{
    public function get()
    {
        if ('top' !== ADMIN) {
            return '';
        }
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $checkUpdateService = App::make('update.check');

        if (
            1
            && sessionWithAdministration()
            && config('system_update_range') !== 'none'
            && RBID === BID
            && SBID === BID
            && ($checkUpdateService->getFinalCheckTime() + 60 * 60) < REQUEST_TIME
        ) {
            try {
                $checkUpdateService->check(phpversion(), CheckForUpdate::PATCH_VERSION);
            } catch (\Exception $e) {
            }
        }

        return $Tpl->get();
    }
}
