<?php

define('ARCHIVES_BACKUP_DIR', SCRIPT_DIR . MEDIA_STORAGE_DIR . 'backup_archives/');
define('DB_FULL_BACKUP_DIR', SCRIPT_DIR . MEDIA_STORAGE_DIR . 'backup_database/');
define('BLOG_EXPORT_DIR', SCRIPT_DIR . MEDIA_STORAGE_DIR . 'backup_blog/');

class ACMS_GET_Admin_Backup_ArchiveZipList extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $zip_list = array();
        $sql_list = array();
        $import_list = array();

        $this->createList(ARCHIVES_BACKUP_DIR, $zip_list);
        $this->createList(DB_FULL_BACKUP_DIR, $sql_list);
        $this->createList(BLOG_EXPORT_DIR, $import_list);

        if (empty($zip_list)) {
            $Tpl->add('notFoundZip');
        } else {
            foreach ($zip_list as $file) {
                $Tpl->add('zip:loop', array(
                    'zipfile' => $file,
                ));
            }
            $Tpl->add('foundZip');
        }

        if (empty($sql_list)) {
            $Tpl->add('notFoundSql');
        } else {
            foreach ($sql_list as $file) {
                $Tpl->add('sql:loop', array(
                    'sqlfile' => $file,
                ));
            }
            $Tpl->add('foundSql');
        }

        if (empty($import_list)) {
            $Tpl->add('notFoundExport');
        } else {
            foreach ($import_list as $file) {
                $Tpl->add('export:loop', array(
                    'zip' => $file,
                ));
            }
            $Tpl->add('foundExport');
        }

        return $Tpl->get();
    }

    function createList($target, &$list)
    {
        $time_list = array(); //ファイルの日付を保存する配列
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
