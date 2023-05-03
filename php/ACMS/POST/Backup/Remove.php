<?php

use Acms\Services\Facades\Storage;

class ACMS_POST_Backup_Remove extends ACMS_POST_Backup_Base
{
    /**
     * @return bool|Field
     */
    public function post()
    {
        try {
            set_time_limit(0);
            $this->authCheck('backup_export');

            $fileName = $this->Post->get('backup_file');
            $type = $this->Post->get('backup_type');

            if (empty($fileName)) {
                throw new \RuntimeException('File name empty.');
            }
            if (!in_array($type, array('database', 'archives'))) {
                throw new \RuntimeException('Wrong type.');
            }
            Storage::remove($this->getPath($type, $fileName));

            $this->addMessage($fileName . ' を削除しました。');
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
        }
        return $this->Post;
    }
}
