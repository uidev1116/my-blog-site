<?php

use Acms\Services\Facades\Common;
use Acms\Services\Facades\Storage;

class ACMS_POST_Entry_Index_Export extends ACMS_POST_Entry_Export
{
    /**
     * @var string
     */
    protected $srcPath;

    /**
     * @return string
     */
    protected $destPath;

    /**
     * @inheritDoc
     */
    function post()
    {
        $this->Post->reset(true);
        $this->Post->setMethod('entry', 'operative', sessionWithCompilation());
        $this->Post->setMethod('checks', 'required');
        $this->Post->validate(new ACMS_Validator());

        if (!$this->Post->isValidAll()) {
            if (!sessionWithCompilation()) {
                $this->addError('権限がありません。');
                AcmsLogger::info('エントリーをエクスポートする権限がないため、処理を中止しました');
            }
            if (empty($this->Post->getArray('checks'))) {
                $this->addError('エントリーが選択されていません。');
            }
            return false;
        }
        if (count($this->Post->getArray('checks')) > 30) {
            $this->addError('一度にエクスポートできるエントリーは30エントリまでです。');
            return false;
        }

        $this->srcPath = MEDIA_STORAGE_DIR . 'entry_tmp/';
        $this->destPath = MEDIA_STORAGE_DIR . 'entries' . date('_Ymd_His') . '.zip';

        DB::setThrowException(true);
        try {
            ignore_user_abort(true);
            set_time_limit(0);

            $export = App::make('entry.export');
            $targetEIDs = [];
            foreach ($this->Post->getArray('checks') as $eid) {
                $id = preg_split('@:@', $eid, 2, PREG_SPLIT_NO_EMPTY);
                $eid = $id[1];
                $export->addEntry($eid);
                $targetEIDs[] = $eid;
            }

            Storage::makeDirectory($this->srcPath);
            $fp = fopen($this->srcPath . 'data.yaml', 'w');
            $fileList = $export->export($fp);
            fclose($fp);

            $this->copyAssets('media', MEDIA_LIBRARY_DIR, $fileList['media']);
            $this->copyAssets('storage', MEDIA_STORAGE_DIR, $fileList['storage']);
            $this->copyAssets('archives', ARCHIVES_DIR, $fileList['archives']);

            Storage::compress(SCRIPT_DIR . $this->srcPath, $this->destPath, 'acms_entry_data');
            Storage::removeDirectory($this->srcPath);

            AcmsLogger::info('指定されたエントリーのエクスポートをしました', [
                'targetEIDs' => $targetEIDs,
            ]);

            $this->download();
        } catch (\Exception $e) {
            $this->Post->set('error', $e->getMessage());
            Storage::removeDirectory($this->srcPath);

            AcmsLogger::warning('指定されたエントリーのエクスポートに失敗しました', Common::exceptionArray($e));
        }
        DB::setThrowException(false);

        return $this->Post;
    }

    /**
     * CopyAssets
     * @param string $type
     * @param string $dir
     * @param array $files
     */
    protected function copyAssets($type, $dir, $files)
    {
        $dest = $this->srcPath . $type . '/';

        foreach ($files as $file) {
            $path = $dir . $file;
            if (!Storage::exists($path)) {
                continue;
            }
            $info = pathinfo($dest . $file);
            $dirname = empty($info['dirname']) ? '' : $info['dirname'] . '/';
            Storage::makeDirectory($dirname);
            Storage::copy($path, $dest . $file);
        }
    }

    /**
     * @return void
     */
    protected function download()
    {
        Common::download($this->destPath, 'entries' . date('_Ymd_His') . '.zip', false, true);
    }
}
