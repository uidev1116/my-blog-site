<?php

use Acms\Services\Facades\Storage;

class ACMS_POST_Backup_ArchiveImport extends ACMS_POST_Backup_Import
{
    public function post()
    {
        try {
            AcmsLogger::info('アーカイブのインポートを実行しました');

            $this->authCheck('backup_import');

            ignore_user_abort(true);
            set_time_limit(0);

            $file_name = $this->Post->get('zipfile', false);
            if (empty($file_name)) {
                throw new \RuntimeException(gettext('バックアップファイルが指定されていません。'));
            }
            Common::backgroundRedirect(acmsLink(array('bid' => RBID)));
            $this->run($file_name);
            die();
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            AcmsLogger::warning('アーカイブのインポート中にエラーが発生しました。', Common::exceptionArray($e));
        }
        return $this->Post;
    }

    /**
     * @param $file_name
     * @throws Exception
     */
    protected function run($file_name)
    {
        $archive_dir = SCRIPT_DIR . ARCHIVES_DIR;
        $media_dir = SCRIPT_DIR . MEDIA_LIBRARY_DIR;
        $storage_dir = SCRIPT_DIR . MEDIA_STORAGE_DIR;

        if (Storage::isFile($this->backupArchivesDir . $file_name)) {
            Storage::removeDirectory($storage_dir . 'archives_tmp');
            Storage::unzip($this->backupArchivesDir . $file_name, $storage_dir);

            $this->renameAllFile($storage_dir . 'archives_tmp/archives/', $archive_dir);
            $this->renameAllFile($storage_dir . 'archives_tmp/media/', $media_dir);
            $this->renameAllFile($storage_dir . 'archives_tmp/storage/', $storage_dir);

            if (Storage::isDirectory($storage_dir . 'archives_tmp/')) {
                Storage::removeDirectory($storage_dir . 'archives_tmp/');
            }
        }
        $field = new Field();
        $field->set('backupFileName', $this->Post->get('zipfile'));
        $this->notify($field);
    }

    /**
     * @param $dir
     * @param $new_dir
     */
    protected function renameAllFile($dir, $new_dir)
    {
        if (Storage::isDirectory($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (filetype($dir . $file) === 'dir') {
                        if ($file === '.' || $file === '..') {
                        } else {
                            $this->renameAllFile($dir . $file . '/', $new_dir . $file . '/');
                        }
                    } else {
                        Storage::makeDirectory($new_dir);
                        Storage::move($dir . $file, $new_dir . $file);
                    }
                }
                closedir($dh);
            }
        }
    }
}
