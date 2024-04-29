<?php

use Acms\Services\Facades\Storage;

class ACMS_POST_Backup_ArchiveExport extends ACMS_POST_Backup_Base
{
    /**
     * @var string
     */
    protected $lockFile;

    public function post()
    {
        try {
            AcmsLogger::info('アーカイブのエクスポートを実行しました');

            $this->authCheck('backup_export');
            ignore_user_abort(true);
            set_time_limit(0);
            $this->lockFile = CACHE_DIR . 'archives-backup-lock';

            if (Storage::exists($this->lockFile)) {
                throw new \RuntimeException(gettext('アーカイブのバックアップを中止しました。すでにバックアップ中の可能性があります。変化がない場合は、cache/archives-backup-lock ファイルを削除してお試しください。'));
            }
            Common::backgroundRedirect(HTTP_REQUEST_URL);
            $this->run();
            die();
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            AcmsLogger::warning('アーカイブのバックアップに失敗しました', Common::exceptionArray($e));
        }
        return $this->Post;
    }

    protected function run()
    {
        Storage::put($this->lockFile, 'lock');
        set_time_limit(0);
        $logger = App::make('archives.logger');

        DB::setThrowException(true);
        try {
            $logger->init();
            Storage::makeDirectory($this->backupArchivesDir);
            $dest = $this->backupArchivesDir . 'archives' . date('_Ymd_Hi') . '.zip';

            $logger->addMessage('archives をバックアップ中...', 5);
            Storage::compress(SCRIPT_DIR . ARCHIVES_DIR, $dest, 'archives_tmp/archives');
            $logger->addMessage('archives のバックアップ完了', 25);

            $logger->addMessage('media をバックアップ中...', 5);
            Storage::compress(SCRIPT_DIR . MEDIA_LIBRARY_DIR, $dest, 'archives_tmp/media');
            $logger->addMessage('media のバックアップ完了', 25);

            $logger->addMessage('storage をバックアップ中...', 5);
            $storageTarget = SCRIPT_DIR . MEDIA_STORAGE_DIR;
            if ($dir = opendir($storageTarget)) {
                while (($file = readdir($dir)) !== false) {
                    if ($file != "." && $file != ".." && substr($file, 0, 1) !== '.') {
                        if (in_array($file, ['backup_archives', 'backup_database' . 'backup_blog'], true)) {
                            continue;
                        }
                        Storage::compress(
                            SCRIPT_DIR . MEDIA_STORAGE_DIR . $file,
                            $dest,
                            'archives_tmp/storage/' . $file
                        );
                    }
                }
                closedir($dir);
            }
            $logger->addMessage('storage のバックアップ完了', 25);

            $logger->addMessage('バックアップ完了', 10);
            $logger->success();
        } catch (\Exception $e) {
            if ($message = $e->getMessage()) {
                $logger->error($message);

                AcmsLogger::warning('アーカイブのバックアップ中にエラーが発生しました。', Common::exceptionArray($e));
            }
        }
        DB::setThrowException(false);

        Storage::remove($this->lockFile);
        sleep(3);
        $logger->terminate();
    }
}
