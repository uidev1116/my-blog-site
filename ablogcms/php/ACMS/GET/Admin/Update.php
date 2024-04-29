<?php

use Acms\Services\Update\Engine;
use Acms\Services\Update\System\CheckForUpdate;
use Acms\Services\Facades\Storage;

class ACMS_GET_Admin_Update extends ACMS_GET_Admin
{
    /**
     * @var Acms\Services\Update\System\CheckForUpdate
     */
    protected $checkUpdateService;

    /**
     * @var Acms\Services\Update\Logger
     */
    protected $logger;

    /**
     * @var Acms\Services\Update\Engine
     */
    protected $updateService;

    /**
     * @var array
     */
    protected $rootVars = [];

    /**
     * @return string
     */
    public function get()
    {
        if (!$this->validate()) {
            return '';
        }
        $this->checkUpdateService = App::make('update.check');
        $this->logger = App::make('update.logger');
        $this->updateService = new Engine($this->logger);
        $this->rootVars = [
            'finalCheckTime' => date('Y/m/d H:i:s', $this->checkUpdateService->getFinalCheckTime()),
        ];

        return $this->build();
    }

    /**
     * @return bool
     */
    protected function validate()
    {
        if ('update' <> ADMIN) {
            return false;
        }
        return true;
    }

    /**
     * アップデート可能なライセンスかチェック
     *
     * @param $version
     * @return bool
     */
    protected function licenseCheck($version)
    {
        list($major, $minor) = explode('.', $version);
        $nextVersion = intval($major) * 100 + intval($minor);
        if ($nextVersion < 211) {
            return true;
        }
        if (defined('LICENSE_PLAN') && LICENSE_PLAN) {
            return true;
        }
        if (intval(LICENSE_SYSTEM_MAJOR_VERSION) < $nextVersion) {
            return false;
        }
        return true;
    }

    /**
     * システムがアップデート中かチェック
     */
    protected function isUpdating()
    {
        if (Storage::exists($this->logger->getDestinationPath())) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    protected function build()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $dbVars = [
            'databaseVersion' => $this->updateService->databaseVersion,
            'systemVersion' => $this->updateService->systemVersion,
        ];

        /**
         * システムアップデート中チェック
         */
        if ($this->isUpdating()) {
            $this->rootVars['processing'] = 1;
        } else {
            $this->rootVars['processing'] = 0;
        }

        /**
         * データベースのバージョンチェック
         */
        $check = $this->updateService->checkUpdates();
        if ($check) {
            $Tpl->add('update', $dbVars);
        } else {
            if ($this->updateService->compareDatabase()) {
                $Tpl->add('match', $dbVars);
            } else {
                $dbVars['diffDB'] = '1';
                $Tpl->add('update', $dbVars);
            }
        }

        /**
         * システムのバージョンチェック
         */
        $range = CheckForUpdate::PATCH_VERSION;
        if (config('system_update_range') === 'minor') {
            $range = CheckForUpdate::MINOR_VERSION;
        }

        /**
         * ダウングレード確認
         */
        if (defined('IS_TRIAL') && IS_TRIAL) {
            if ($this->checkUpdateService->checkDownGradeUseCache(phpversion())) {
                $version = $this->checkUpdateService->getDownGradeVersion();
                $Tpl->add('downgrade', [
                    'version' => $version,
                    'downloadUrl' => $this->checkUpdateService->getDownGradePackageUrl(),
                ]);
            }
        } else {
            /**
             * アップデート確認
             */
            if ($this->checkUpdateService->checkUseCache(phpversion(), $range)) {
                $version = $this->checkUpdateService->getUpdateVersion();
                $Tpl->add('oldVersion', [
                    'version' => $version,
                    'downloadUrl' => $this->checkUpdateService->getPackageUrl(),
                    'oldLicense' => $this->licenseCheck($version) ? 'no' : 'yes',
                ]);
                if ($releaseNote = $this->checkUpdateService->getReleaseNote()) {
                    foreach ($releaseNote as $note) {
                        foreach ($note->logs->features as $message) {
                            $Tpl->add(['feature:loop', 'version:loop', 'changelog'], [
                                'log' => $message,
                            ]);
                        }
                        foreach ($note->logs->changes as $message) {
                            $Tpl->add(['change:loop', 'version:loop', 'changelog'], [
                                'log' => $message,
                            ]);
                        }
                        foreach ($note->logs->fix as $message) {
                            $Tpl->add(['fix:loop', 'version:loop', 'changelog'], [
                                'log' => $message,
                            ]);
                        }
                        $Tpl->add(['version:loop', 'changelog'], [
                            'version' => $note->version,
                            'alert' => $note->alert,
                        ]);
                    }
                    $Tpl->add('changelog', [
                        'url' => $this->checkUpdateService->getChangelogUrl(),
                    ]);
                }
            } else {
                if (config('system_update_range') === 'minor') {
                    $Tpl->add('latest:minor', []);
                } else {
                    $Tpl->add('latest:patch', []);
                }
            }
        }
        $Tpl->add(null, $this->rootVars);

        return $Tpl->get();
    }
}
