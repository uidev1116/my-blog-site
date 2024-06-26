<?php

use Acms\Services\Facades\Storage;

class ACMS_POST_Entry_Import extends ACMS_POST_Entry
{
    /**
     * @var string $tmpDir
     */
    private $tmpDir;

    /**
     * @inheritDoc
     */
    public function post()
    {
        @set_time_limit(0);
        DB::setThrowException(true);

        if (!sessionWithCompilation()) {
            return $this->Post;
        }

        try {
            AcmsLogger::info('エントリーのインポートを開始しました');

            $this->tmpDir = MEDIA_STORAGE_DIR . 'entry_data/';
            $import = App::make('entry.import');
            $status = $this->Post->get('entry_status');
            $distPath = sprintf("%03d", BID) . '/import' . date('YmdHis', REQUEST_TIME) . '/';

            $zipFilePath = $this->getZipFile();
            $this->decompress($zipFilePath);
            $yaml = $this->getYaml();
            $errors = $import->run(BID, $yaml, $distPath, $status);
            $this->copyAssets($distPath);

            if (empty($errors)) {
                $this->addMessage('インポートに成功しました。');
                AcmsLogger::info('エントリーのインポートが成功しました');
            } else {
                AcmsLogger::info('エントリーのインポートでエラーが発生しました', $errors);
            }
            foreach ($errors as $error) {
                $this->addError($error);
            }
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
            AcmsLogger::info('エントリーのインポートが失敗しました', Common::exceptionArray($e));
        }

        DB::setThrowException(false);
        Storage::removeDirectory($this->tmpDir);

        return $this->Post;
    }

    /**
     * get zip file path
     *
     * @return string
     * @throws RuntimeException
     */
    private function getZipFile()
    {
        $uploadFile = '';

        if (isset($_FILES['entry_export_data']['tmp_name'])) {
            $uploadFile = $_FILES['entry_export_data']['tmp_name'];
        }
        if (is_uploaded_file($uploadFile)) {
            // アップロードされたファイルを利用
            return $uploadFile;
        }
        throw new RuntimeException('zipファイルのアップロードに失敗しました。ファイルサイズが大きすぎる可能性があります。');
    }

    /**
     * get yaml data
     *
     * @return string
     */
    private function getYaml()
    {
        $yamlPath = $this->tmpDir . 'acms_entry_data/data.yaml';
        try {
            return Storage::get($yamlPath, dirname($yamlPath));
        } catch (\Exception $e) {
            $yamlPath = $this->tmpDir . 'data.yaml';
            return Storage::get($yamlPath, dirname($yamlPath));
        }
        throw new \RuntimeException('File does not exist.');
    }

    /**
     * decompress zip
     *
     * @param string $path
     * @return bool
     */
    private function decompress($path)
    {
        Storage::makeDirectory($this->tmpDir);
        Storage::unzip($path, $this->tmpDir);

        return true;
    }

    /**
     * copy assets
     *
     * @param string $distPath
     * @return void
     */
    private function copyAssets($distPath)
    {
        $list = [
            'archives/' => ARCHIVES_DIR,
            'media/' => MEDIA_LIBRARY_DIR,
            'storage/' => MEDIA_STORAGE_DIR,
        ];
        foreach ($list as $from => $to) {
            $exists = false;
            $from2 = $this->tmpDir . 'acms_entry_data/' . $from;
            if (Storage::exists($from2)) {
                $exists = true;
            } else {
                $from2 = $this->tmpDir . $from;
                if (Storage::exists($from2)) {
                    $exists = true;
                }
            }
            if ($exists) {
                $to = SCRIPT_DIR . $to . $distPath;
                Storage::copyDirectory($from2, $to);
            }
        }
    }
}
