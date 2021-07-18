<?php

define('ARCHIVES_BACKUP_DIR', SCRIPT_DIR.ARCHIVES_DIR.'backup/');
define('DB_FULL_BACKUP_DIR', SCRIPT_DIR.'private/backup/');

class ACMS_GET_Utility_ArchiveZipList extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        
        $zip_list = array();
        $sql_list = array();
        
        if(Storage::isDirectory(ARCHIVES_BACKUP_DIR)){
            if ($dir = opendir(ARCHIVES_BACKUP_DIR)) {
                while (($file = readdir($dir)) !== false) {
                    if ($file != "." && $file != ".." && substr($file,0,1) != '.') {
                        $zip_list[] = array(
                            'zipfile' => $file
                        );
                    }
                }
                closedir($dir);
            }
        }
        
        if(Storage::isDirectory(DB_FULL_BACKUP_DIR)){
            if ($dir = opendir(DB_FULL_BACKUP_DIR)) {
                while (($file = readdir($dir)) !== false) {
                    if ($file != "." && $file != ".." && substr($file,0,1) != '.') {
                        $sql_list[] = array(
                            'sqlfile' => $file
                        );
                    }
                }
                closedir($dir);
            }
        }
        
        foreach($zip_list as $loop){
            $Tpl->add('zip:loop', $loop);
        }
        
        foreach($sql_list as $loop){
            $Tpl->add('sql:loop', $loop);
        }

        return $Tpl->get();
    }
}
