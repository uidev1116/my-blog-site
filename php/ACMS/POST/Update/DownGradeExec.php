<?php

use Acms\Services\Facades\Storage;
use Acms\Services\Update\Engine;
use Acms\Services\Facades\Common;

class ACMS_POST_Update_DownGradeExec extends ACMS_POST_Update_Base
{
    /**
     * @var bool
     */
    protected $newSetup = false;

    public function post()
    {
        if (!sessionWithAdministration()) die();
        if ('update' <> ADMIN) die();
        if (RBID !== BID) die();
        if (SBID !== BID) die();

        ignore_user_abort(true);
        set_time_limit(0);

        if ($this->isProcessing()) {
            $this->addError(gettext('ダウングレードを中止しました。すでにダウングレード中の可能性があります。変化がない場合は、cache/system-update-lock ファイルを削除してお試しください。'));
            return $this->Post;
        }
        $this->newSetup= $this->Post->get('new_setup') === 'create';
        Common::backgroundRedirect(HTTP_REQUEST_URL);
        $this->run();
        die();
    }

    /**
     * Run update.
     */
    protected function run()
    {
        $this->createLockFile();

        set_time_limit(0);

        $logger = App::make('update.logger');
        $downloadService = App::make('update.download');
        $placeFileService = App::make('update.place.file');
        $checkUpdateService = App::make('update.check');
        $dest = ARCHIVES_DIR . uniqueString() . '/';

        DB::setThrowException(true);

        try {
            $logger->init();
            if ($checkUpdateService->checkDownGradeUseCache(phpversion())) {
                $version = $checkUpdateService;
            } else {
                throw new \RuntimeException(gettext('パッケージ情報の取得に失敗しました'));
            }

            $url = $version->getDownGradePackageUrl();
            $root_dir = $version->getRootDir();

            $dbUpdateService = new Engine($logger);
            $dbUpdateService->setUpdateVersion($version->getDownGradeVersion());
            $dbUpdateService->validate(true);

            $new_path = $dest . $root_dir;
            $backup_dir = 'private/' . 'backup' . date('YmdHis') . '/';
            if (!Storage::isWritable('private')) {
                throw new \RuntimeException(gettext('privateディレクトリに書き込み権限を与えてください'));
            }

            // validate
            $placeFileService->validate($new_path, $backup_dir);

            // Update system file
            $downloadService->download($dest, $url);
            $placeFileService->exec($new_path, $backup_dir, $this->newSetup);

            $logger->fileUpdateSuccess();
            setTrial(); // トライアルの日付を更新

            // Update database
            $dbUpdateService->update();

            $logger->success();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $logger->error($e->getMessage());
            }
            sleep(3);
            $logger->terminate();
        }

        DB::setThrowException(false);
        $logger->addMessage(gettext('ダウンロードファイルを削除中...'), 0);
        $this->removeDirectory($dest);
        $this->removeLockFile();

        sleep(3);
        $logger->terminate();
    }

    protected function removeDirectory($path)
    {
        if (PHP_OS === 'Windows') {
            exec(sprintf("rd /s /q %s", escapeshellarg($path)));
        } else {
            exec(sprintf("rm -rf %s", escapeshellarg($path)));
        }
        Storage::removeDirectory($path);
    }
}
