<?php

use Acms\Services\Facades\Storage;
use Symfony\Component\Finder\Finder;

class ACMS_POST_Update_RemoveBackup extends ACMS_POST_Update_Base
{
    public function post()
    {
        $finder = new Finder();
        $lists = array();
        $iterator = $finder
            ->in('private')
            ->depth('< 2')
            ->name('/^backup.+/')
            ->directories();

        foreach ($iterator as $dir) {
            $lists[] = $dir->getRelativePathname();
        }
        foreach ($lists as $item) {
            try {
                Storage::removeDirectory('private/' . $item);
            } catch (\Exception $e) {}
        }
        $this->addMessage(gettext('バックアップを削除しました。'));
        return $this->Post;
    }
}