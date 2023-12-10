<?php

use Acms\Services\Facades\Storage;
use Symfony\Component\Finder\Finder;

/**
 * Class ACMS_POST_Backup_BlogImport
 */
class ACMS_POST_Backup_BlogImport extends ACMS_POST_Backup_Base
{
    /**
     * @var string $tmpDir
     */
    private $tmpDir;

    /**
     * run
     *
     * @return Field
     */
    public function post()
    {
        @set_time_limit(0);
        DB::setThrowException(true);

        try {
            AcmsLogger::info('「' . ACMS_RAM::blogName(BID) . '」ブログのインポートを実行しました');

            $this->authCheck('backup_import');
            $this->tmpDir = MEDIA_STORAGE_DIR . 'blog_data/';
            $import = App::make('blog.import');

            $this->decompress();
            $this->deleteArchives();
            $this->copyArchives();

            $yaml = $this->getYaml();
            $yaml = $this->fixYaml($yaml);
            $errors = $import->run(BID, $yaml);

            if (empty($errors)) {
                Cache::flush('template');
                Cache::flush('config');
                Cache::flush('field');
                Cache::flush('temp');

                $this->Post->set('import', 'success');
            }
            foreach ($errors as $error) {
                $this->addError($error);
            }
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            $this->deleteArchives();
        }
        DB::setThrowException(false);

        Storage::removeDirectory($this->tmpDir);

        return $this->Post;
    }

    /**
     * get yaml data
     *
     * @return string
     */
    private function getYaml()
    {
        try {
            return Storage::get($this->tmpDir . 'acms_blog_data/data.yaml');
        } catch (\Exception $e) {
            return Storage::get($this->tmpDir . 'data.yaml');
        }
        throw new \RuntimeException('File does not exist.');
    }

    /**
     * fix blog id
     *
     * @param string $yaml
     *
     * @return string
     */
    private function fixYaml($yaml)
    {
        return preg_replace('@([\d]{3})/(.*)\.([^\.]{2,6})@ui', sprintf("%03d", BID) . '/$2.$3', $yaml, -1);
    }

    /**
     * decompress zip
     *
     * @return bool
     */
    private function decompress()
    {
        $file = $this->Post->get('zipfile', false);
        if (!$file) {
            return false;
        }
        if (!Storage::isFile($this->backupBlogDir . $file)) {
            return false;
        }
        Storage::makeDirectory($this->tmpDir);
        Storage::unzip($this->backupBlogDir . $file, $this->tmpDir);

        return true;
    }

    /**
     * delete archives
     *
     * @return void
     */
    private function deleteArchives()
    {
        foreach (array(ARCHIVES_DIR, MEDIA_LIBRARY_DIR, MEDIA_STORAGE_DIR) as $baseDir) {
            $target = SCRIPT_DIR . $baseDir . sprintf("%03d", BID) . '/';
            if (Storage::isDirectory($target)) {
                Storage::removeDirectory($target);
            }
        }
    }

    /**
     * copy archives directory
     *
     * @return void
     */
    private function copyArchives()
    {
        $list = array(
            'archives/' => ARCHIVES_DIR,
            'media/' => MEDIA_LIBRARY_DIR,
            'storage/' => MEDIA_STORAGE_DIR,
        );
        foreach ($list as $from => $to) {
            $exists = false;
            $from = $this->tmpDir . 'acms_blog_data/' . $from . '001/';
            if (Storage::exists($from)) {
                $exists = true;
            } else {
                $from = $this->tmpDir . $from . '001/';
                if (Storage::exists($from)) {
                    $exists = true;
                }
            }
            if ($exists) {
                $to = SCRIPT_DIR . $to . sprintf("%03d", BID) . '/';
                Storage::copyDirectory($from, $to);
            }
        }
    }
}
