<?php

use Acms\Services\Facades\Storage;

class ACMS_POST_File extends ACMS_POST
{
    var $delete;
    var $ARCHIVES_DIR;

    var $olddel;
    var $directAdd;
    var $moveArchive;

    /*
     * Image = new ACMS_File();
     *
     */
    public function __construct($olddel=true, $directAdd=false, $moveArchive='')
    {
        //-------
        // init
        $this->pattern      = '@'.REVISON_ARCHIVES_DIR.'@';
        $this->delete       = null;

        $this->olddel       = $olddel;
        $this->directAdd    = $directAdd;
        $this->moveArchive  = $moveArchive;

        $this->ARCHIVES_DIR = ARCHIVES_DIR;
        if ( !empty($moveArchive) ) {
            $this->ARCHIVES_DIR = ARCHIVES_DIR.'TEMP/';
        }
    }

    public function buildAndSave($id, $old, $FILES, $name, $n, $edit)
    {
        $this->id           = $id;
        $this->delete       = null;
        $this->old          = ltrim($old, './');
        $this->edit         = $edit;
        $this->pathArray    = array();
        $this->num          = $n;

        $pattern    = '@'.REVISON_ARCHIVES_DIR.'@';
        $this->old  = preg_replace($pattern, '', $this->old, 1);

        //----------------
        // build and save
        $fileArray = array();

        foreach ( $this->buildFileData($FILES, $name) as $fileData ) {
            if ( empty($fileData) ) continue;
            array_push($fileArray, $fileData);
        }
        $this->editAndSaveFiles($fileArray);
        $this->deleteFiles();

        return $this->pathArray;
    }

    private function buildFileData($FILES, $name)
    {
        $files = array();

        if ( 'delete' === $this->edit ) {
            $this->delete      = $this->ARCHIVES_DIR.$this->old;
            $this->pathArray[] = '';
        } else {
            if ( 1
                && isset($FILES)
                && is_array($FILES)
            ) {
                for ( $m = 0; $m < count($FILES); $m++ ) {
                    if ( ( is_uploaded_file($FILES[$m]) ) and preg_match('@^([^/]+)\.([^./]+)$@', $name[$m]) ) {
                        $files[]    = array(
                            'tmp_name'  => $FILES[$m],
                            'name'      => $name[$m],
                        );
                    }
                }
            } else if ( 1
                && isset($FILES)
                && ( $this->directAdd || is_uploaded_file($FILES) ) and preg_match('@^([^/]+)\.([^./]+)$@', $name)
            ) {
                $files[]  = array(
                    'tmp_name'  => $FILES,
                    'name'      => $name,
                );
            }

            if ( empty($files) ) {
                $this->pathArray[]    = $this->old;
            }
        }
        return $files;
    }

    private function editAndSaveFiles($files=array())
    {
        foreach ( $files as $value ) {
            $ufile  = $value['tmp_name'];
            $fname  = $value['name'];
            if ( 1
                and ( $this->directAdd || is_uploaded_file($ufile) )
                and preg_match('@^([^/]+)\.([^./]+)$@', $fname, $match)
            ) {
                $basename   = $match[0];
                $extension  = strtolower($match[2]);

                if ( in_array($extension, array_merge(
                    configArray('file_extension_document'),
                    configArray('file_extension_archive'),
                    configArray('file_extension_movie'),
                    configArray('file_extension_audio')
                )) ) {
                    $dir    = Storage::archivesDir();
                    Storage::makeDirectory($this->ARCHIVES_DIR.$dir);

                    $path   = ('rawfilename' == config('file_savename'))
                        ? $dir.$basename : $dir.uniqueString().'.'.$extension;

                    // 重複対応
                    $path   = Storage::uniqueFilePath($this->ARCHIVES_DIR . $path);
                    $path   = mb_substr($path, strlen($this->ARCHIVES_DIR));

                    Storage::copy($ufile, $this->ARCHIVES_DIR.$path);

                    if ( HOOK_ENABLE ) {
                        $Hook = ACMS_Hook::singleton();
                        $Hook->call('mediaCreate', $this->ARCHIVES_DIR.$path);
                    }

                    if ( 1
                        and empty($this->delete)
                        and !empty($this->old)
                        and $this->old <> $path
                    ) {
                        $this->delete     = $this->ARCHIVES_DIR.$this->old;
                    }
                    $this->pathArray[]    = $path;
                }
            }
        }
    }

    private function deleteFiles()
    {
        if ( $this->olddel === TRUE ) {
            if ( !empty($this->delete) ) {
                deleteFile($this->delete);
            }
        }
    }
}
