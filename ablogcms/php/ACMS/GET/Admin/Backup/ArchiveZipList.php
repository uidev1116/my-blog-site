<?php

class ACMS_GET_Admin_Backup_ArchiveZipList extends ACMS_GET
{
    public function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $zip_list = [];
        $sql_list = [];
        $import_list = [];

        $archivesBackupDir = MEDIA_STORAGE_DIR . 'backup_archives/';
        $dbBackupDir = MEDIA_STORAGE_DIR . 'backup_database/';
        $blogBackupDir = MEDIA_STORAGE_DIR . 'backup_blog/';
        $this->createList($archivesBackupDir, $zip_list);
        $this->createList($dbBackupDir, $sql_list);
        $this->createList($blogBackupDir, $import_list);

        if (empty($zip_list)) {
            $Tpl->add('notFoundZip');
        } else {
            foreach ($zip_list as $file) {
                $Tpl->add('zip:loop', [
                    'zipfile' => $file,
                ]);
            }
            $Tpl->add('foundZip');
        }

        if (empty($sql_list)) {
            $Tpl->add('notFoundSql');
        } else {
            foreach ($sql_list as $file) {
                $Tpl->add('sql:loop', [
                    'sqlfile' => $file,
                ]);
            }
            $Tpl->add('foundSql');
        }

        if (empty($import_list)) {
            $Tpl->add('notFoundExport');
        } else {
            foreach ($import_list as $file) {
                $Tpl->add('export:loop', [
                    'zip' => $file,
                ]);
            }
            $Tpl->add('foundExport');
        }

        return $Tpl->get();
    }

    private function createList($target, &$list)
    {
        $time_list = []; //ファイルの日付を保存する配列
        if (Storage::isDirectory($target)) {
            if ($dir = opendir($target)) {
                while (($file = readdir($dir)) !== false) {
                    if ($file != "." && $file != ".." && substr($file, 0, 1) != '.') {
                        $list[] = $file;
                        $time_list[] = filemtime($target . $file);
                    }
                }
                closedir($dir);
            }
        }
        array_multisort($time_list, SORT_DESC, $list); //時刻でソート
    }
}
