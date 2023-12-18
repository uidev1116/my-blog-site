<?php

use Acms\Services\Facades\Storage;
use Acms\Services\Update\Engine;
use Acms\Services\Update\System\CheckForUpdate;
use Acms\Services\Facades\Common;

class ACMS_POST_Update_Exec extends ACMS_POST_Update_Base
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
            $this->addError(gettext('アップデートを中止しました。すでにアップデート中の可能性があります。変化がない場合は、cache/system-update-lock ファイルを削除してお試しください。'));
            return $this->Post;
        }
        $this->newSetup= $this->Post->get('new_setup') === 'create';
        AcmsLogger::info('アップデートを開始しました');

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
            $range = CheckForUpdate::PATCH_VERSION;
            if (config('system_update_range') === 'minor') {
                $range = CheckForUpdate::MINOR_VERSION;
            }
            if ($checkUpdateService->checkUseCache(phpversion(), $range)) {
                $version = $checkUpdateService;
            } else {
                throw new \RuntimeException(gettext('パッケージ情報の取得に失敗しました'));
            }

            $url = $version->getPackageUrl();
            $root_dir = $version->getRootDir();

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

            // Update database
            $dbUpdateService = new Engine($logger);
            $dbUpdateService->setUpdateVersion($version->getUpdateVersion());
            $dbUpdateService->validate(true);
            $dbUpdateService->update();

            setTrial(); // トライアルの日付を更新
            $logger->success();

            AcmsLogger::info('アップデートが完了しました');
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $logger->error($e->getMessage());
            }
            sleep(3);
            $logger->terminate();

            AcmsLogger::warning('アップデートに失敗しました。' . $e->getMessage(), Common::exceptionArray($e));
        }

        DB::setThrowException(false);
        $logger->addMessage(gettext('ダウンロードファイルを削除中...'), 0);
        $this->removeDirectory($dest);
        $this->removeLockFile();

        // opcodeキャッシュをリセット
        if (function_exists("opcache_reset")) {
            opcache_reset();
        }
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
