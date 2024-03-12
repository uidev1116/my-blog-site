<?php

use Acms\Services\Facades\Storage;

class ACMS_POST_Update_Base extends ACMS_POST
{
    protected $lockFile;

    public function __construct()
    {
        $this->lockFile = CACHE_DIR . 'system-update-lock';
    }

    protected function isProcessing()
    {
        return Storage::exists($this->lockFile);
    }

    protected function createLockFile()
    {
        Storage::put($this->lockFile, 'lock');
    }

    protected function removeLockFile()
    {
        Storage::remove($this->lockFile);
    }
}
