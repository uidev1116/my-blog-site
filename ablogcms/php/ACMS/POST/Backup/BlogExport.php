<?php

use Acms\Services\Facades\Common;
use Acms\Services\Facades\Storage;

/**
 * Class ACMS_POST_Backup_BlogExport
 */
class ACMS_POST_Backup_BlogExport extends ACMS_POST_Backup_Base
{
    /**
     * @var string $yaml
     */
    protected $yaml;

    /**
     * @var string $srcPath
     */
    private $srcPath;

    /**
     * @var string $destPath
     */
    private $destPath;

    /**
     * run
     *
     * @inheritDoc
     */
    public function post()
    {
        try {
            AcmsLogger::info('「' . ACMS_RAM::blogName(BID) . '」ブログのエクスポートを実行しました');

            $this->authCheck('backup_export');

            ignore_user_abort(true);
            set_time_limit(0);

            $export = App::make('blog.export');
            $this->srcPath = MEDIA_STORAGE_DIR . 'blog_tmp/';
            $this->destPath = MEDIA_STORAGE_DIR . 'blog' . date('_Ymd_His') . '.zip';

            Storage::makeDirectory($this->srcPath);
            $fp = fopen($this->srcPath . 'data.yaml', 'w');
            $mediaPaths = $export->export($fp, BID);
            fclose($fp);

            $this->copyArchives();
            $this->copyMedia($mediaPaths);

            Storage::compress($this->srcPath, $this->destPath, 'acms_blog_data');
            Storage::removeDirectory($this->srcPath);
            $this->download();
        } catch (\Exception $e) {
            $this->Post->set('error', $e->getMessage());
            Storage::removeDirectory($this->srcPath);

            AcmsLogger::warning('ブログのエクスポート中にエラーが発生しました。', Common::exceptionArray($e));
        }
        return $this->Post;
    }

    /**
     * download yaml data
     *
     * @return void
     */
    private function download()
    {
        Common::download($this->destPath, 'blog' . date('_Ymd_His') . '.zip', false, true);
    }

    /**
     * copy archives directory
     *
     * @return void
     */
    private function copyArchives()
    {
        $archive_path = ARCHIVES_DIR . sprintf("%03d", BID) . '/';
        if (!Storage::exists($archive_path)) {
            return;
        }
        $archive_tmp = $this->srcPath . 'archives/001';
        Storage::copyDirectory($archive_path, $archive_tmp);
    }

    /**
     * copy media directory
     *
     * @param array<array{type: string, path: string}> $mediaPaths
     */
    private function copyMedia(array $mediaPaths): void
    {
        $mediaPath = MEDIA_LIBRARY_DIR . sprintf("%03d", BID) . '/';
        $mediaFilePath = MEDIA_STORAGE_DIR . sprintf("%03d", BID) . '/';
        if (Storage::exists($mediaPath)) {
            $mediaTemp = $this->srcPath . 'media/001';
            Storage::copyDirectory($mediaPath, $mediaTemp);
        }
        if (Storage::exists($mediaFilePath)) {
            $mediaTemp = $this->srcPath . 'storage/001';
            Storage::copyDirectory($mediaFilePath, $mediaTemp);
        }
        // エクスポートブログとは異なるブログのメディアファイルを個々にコピー
        foreach ($mediaPaths as $media) {
            $type = $media['type'];
            $path = $media['path'];

            if ($type === 'image' || $type === 'svg') {
                $srcPath =  MEDIA_LIBRARY_DIR . $path;
                if (Storage::exists($srcPath)) {
                    $destPath = "{$this->srcPath}media/001/" . preg_replace('/^\d{3}\//', '', $path);
                    Storage::makeDirectory(dirname($destPath));
                    Storage::copy($srcPath, $destPath);
                }
                if ($type === 'image') {
                    foreach (['large', 'tiny', 'square'] as $size) {
                        $otherPath = otherSizeImagePath($path, $size);
                        $srcPath =  MEDIA_LIBRARY_DIR . $otherPath;
                        if (Storage::exists($srcPath)) {
                            $destPath = "{$this->srcPath}media/001/" . preg_replace('/^\d{3}\//', '', $otherPath);
                            Storage::makeDirectory(dirname($destPath));
                            Storage::copy($srcPath, $destPath);
                        }
                    }
                }
            }
            if ($type === 'file') {
                $srcPath =  MEDIA_STORAGE_DIR . $path;
                if (Storage::exists($srcPath)) {
                    $destPath = "{$this->srcPath}storage/001/" . preg_replace('/^\d{3}\//', '', $path);
                    Storage::makeDirectory(dirname($destPath));
                    Storage::copy($srcPath, $destPath);
                }
            }
        }
    }
}
