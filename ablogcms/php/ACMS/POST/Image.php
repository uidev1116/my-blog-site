<?php

class ACMS_POST_Image extends ACMS_POST
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $old;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string|int
     */
    public $size;

    /**
     * @var string|int
     */
    public $selectSize;

    /**
     * @var string|int
     */
    public $oldSize;

    /**
     * @var string
     */
    public $edit;

    /**
     * @var string|null
     */
    public $delete;

    /**
     * @var int|null
     */
    public $angle;

    /**
     * @var string
     */
    public $ARCHIVES_DIR;

    /**
     * @var bool
     */
    public $olddel;

    /**
     * @var bool
     */
    public $directAdd;

    /**
     * @var string
     */
    public $target;

    /**
     * @var int
     */
    public $tinySize;

    /**
     * @var int
     */
    public $largeSize;

    /**
     * @var int
     */
    public $squareSize;

    /**
     * @var string|null
     */
    public $stdSide;

    /**
     * @var string|null
     */
    public $stdSideTiny;

    /**
     * @var string|null
     */
    public $stdSideLarge;

    /*
     * Image = new ACMS_POST_Image();
     */
    public function __construct($olddel = true, $directAdd = false)
    {
        //-------
        // init
        $this->delete       = null;
        $this->angle        = null;

        $this->olddel       = $olddel;
        $this->directAdd    = $directAdd;
        $this->ARCHIVES_DIR = ARCHIVES_DIR;
    }

    public static function getBase64Data($string)
    {
        $temp = explode(',', $string);
        if (count($temp) > 1) {
            $data = $temp[1];
        } else {
            $data = $temp[0];
        }
        return $data;
    }

    public static function base64DataToImage($base64, $id, $index = false)
    {
        if (empty($base64)) {
            return false;
        }
        if (!Storage::exists(ARCHIVES_DIR . 'tmp/')) {
            if (!Storage::makeDirectory(ARCHIVES_DIR . 'tmp/')) {
                return false;
            }
        }
        $name       = $_FILES[$id]['name'];
        $type       = $_FILES[$id]['type'];
        $tmp_name   = $_FILES[$id]['tmp_name'];
        $error      = $_FILES[$id]['error'];
        $size       = $_FILES[$id]['size'];

        //----------------
        // 複数アップロード
        if (is_array($base64)) {
            foreach ($base64 as $i => $row) {
                $row = self::getBase64Data($row);
                if (empty($row)) {
                    continue;
                }
                try {
                    $tmpFile    = uniqueString() . '.jpeg';
                    $data       = base64_decode($row);
                    $dest       = ARCHIVES_DIR . 'tmp/' . $tmpFile;

                    Storage::put($dest, $data);
                    $tmpPath    = realpath($dest);

                    $name[$i]     = $tmpFile;
                    $type[$i]     = 'image/jpeg';
                    $tmp_name[$i] = $tmpPath;
                    $error[$i]    = '';
                    $size[$i]     = filesize($dest);
                } catch (\Exception $e) {
                    AcmsLogger::notice($e->getMessage(), Common::exceptionArray($e));
                    continue;
                }
            }

            //----------------
            // 単数アップロード
        } else {
            try {
                $tmpFile    = uniqueString() . '.jpeg';
                $base64     = self::getBase64Data($base64);
                $data       = base64_decode($base64);
                $dest       = ARCHIVES_DIR . 'tmp/' . $tmpFile;

                Storage::put($dest, $data);
                $tmpPath    = realpath($dest);

                // 多言語対応
                if (is_int($index)) {
                    $name[$index]       = $tmpFile;
                    $type[$index]       = 'image/jpeg';
                    $tmp_name[$index]   = $tmpPath;
                    $error[$index]      = '';
                    $size[$index]       = filesize($dest);
                } else {
                    $name       = $tmpFile;
                    $type       = 'image/jpeg';
                    $tmp_name   = $tmpPath;
                    $error      = '';
                    $size       = filesize($dest);
                }
            } catch (\Exception $e) {
                AcmsLogger::notice($e->getMessage(), Common::exceptionArray($e));
            }
        }

        if (empty($name)) {
            return false;
        }

        $_FILES[$id] = array(
            'name'      => $name,
            'type'      => $type,
            'tmp_name'  => $tmp_name,
            'error'     => $error,
            'size'      => $size,
        );
    }

    /**
     * @param string $id
     * @param string|null $old 保存されている画像パス
     * @param array|string $FILES
     * @param string $size ユニットで選択された画像サイズ ex: width820:acms-col-sm-12,
     * @param 'none' | 'deleteLarge' | 'rotate270' | 'rotate90' | 'rotate180' | 'delete' $edit
     * @param string $size_old 現在保存されている画像のサイズ ex: with820:acms-col-sm-12, w820
     */
    public function buildAndSave($id, $old, $FILES, $size, $edit, $size_old = '')
    {
        $this->id       = $id;
        $this->old      = $old;
        $this->selectSize = $size;
        $this->oldSize  = $size_old;
        $this->edit     = $edit;
        $this->delete   = null;

        $this->old      = is_string($old) ? ltrim($old, './') : $old;
        $this->path     = $this->old;

        if ('delete' == $this->edit) {
            if (!empty($this->old)) {
                $this->delete = $this->ARCHIVES_DIR . $this->old;
            }
            $this->old  = null;
        } elseif ('deleteLarge' == $this->edit) {
            if (!empty($this->old)) {
                $file = $this->ARCHIVES_DIR . $this->old;
                if (Storage::isFile($file)) {
                    $name   = Storage::mbBasename($file);
                    $dir    = substr($file, 0, (strlen($file) - strlen($name)));
                    Storage::remove($dir . 'large-' . $name);
                    if (HOOK_ENABLE) {
                        $Hook = ACMS_Hook::singleton();
                        $Hook->call('mediaDelete', $dir . 'large-' . $name);
                    }
                }
            }
        } elseif ('rotate' == substr($edit, 0, 6)) {
            $this->angle    = intval(substr($edit, 6));
        }

        //----------------
        // build and save
        $imageFiles = array();

        $this->buildSize($size);
        foreach ($this->buildInsertData($FILES) as $imageData) {
            if (empty($imageData)) {
                continue;
            }
            array_push($imageFiles, $imageData);
        }
        foreach ($this->buildUpdateData() as $imageData) {
            if (empty($imageData)) {
                continue;
            }
            array_push($imageFiles, $imageData);
        }
        $this->editAndSaveImage($imageFiles);
        $this->deleteImage();
        Storage::removeDirectory(ARCHIVES_DIR . 'tmp/');

        return $imageFiles;
    }

    private function buildInsertData($FILES)
    {
        $file   = null;
        $Edit   = array();

        $uploadFiles    = array();
        $imageFiles     = array();
        do {
            if (!empty($this->delete)) {
                break;
            }
            if (empty($FILES)) {
                break;
            }

            //----------------
            // 複数アップロード
            if (is_array($FILES)) {
                if (!empty($this->old)) {
                    $uploadFiles  = $FILES;
                    if (
                        1
                        && ($this->directAdd || is_uploaded_file($uploadFiles[0]))
                        && Storage::getImageSize($uploadFiles[0])
                    ) {
                        $this->delete   = $this->ARCHIVES_DIR . $this->old;
                        $this->old      = null;
                    } else {
                        continue;
                    }
                } else {
                    $uploadFiles = $FILES;
                }
                //-----------------
                // 単数アップロード
            } else {
                $uploadFiles[] = $FILES;

                if (
                    0
                    || !Storage::getImageSize($uploadFiles[0])
                    || (!is_uploaded_file($uploadFiles[0]) && !$this->directAdd)
                ) {
                    break;
                }
                if (!empty($this->old)) {
                    $this->delete = $this->ARCHIVES_DIR . $this->old;
                    $this->old    = null;
                }
            }

            //------------------------
            // アップロード ファイル情報
            foreach ($uploadFiles as $j => $upload) {
                if (!$this->directAdd && !is_uploaded_file($upload)) {
                    continue;
                }
                if (!$xy = Storage::getImageSize($upload)) {
                    continue;
                }

                $longSide   = max($xy[0], $xy[1]);
                $mime       = $xy['mime'];

                $Edit['tiny']['size']   = $this->tinySize;
                if ($this->squareSize > 0) {
                    $Edit['square']['size'] = $this->squareSize;
                }
                $Edit['normal']['size'] = $this->size;

                if (!empty($this->angle)) {
                    $Edit['tiny']['angle']          = $this->angle;
                    if ($this->squareSize > 0) {
                        $Edit['square']['angle']    = $this->angle;
                    }
                    $Edit['normal']['angle']        = $this->angle;
                }

                if (!empty($this->size) && $longSide > $this->size && 'deleteLarge' !== $this->edit) {
                    $Edit['large']['size']  = ($longSide > $this->largeSize) ? $this->largeSize : $longSide;
                    if (!empty($this->angle)) {
                        $Edit['large']['angle']     = $this->angle;
                    }
                }

                $this->target   = $upload;
                $dir            = Storage::archivesDir();
                Storage::makeDirectory($this->ARCHIVES_DIR . $dir);
                $exts   = array(
                    'image/gif'         => 'gif',
                    'image/png'         => 'png',
                    'image/vnd.wap.wbmp' => 'bmp',
                    'image/xbm'         => 'xbm',
                    'image/jpeg'        => 'jpg',
                );
                $ext    = isset($exts[$mime]) ? $exts[$mime] : 'jpg';
                $path   = $dir . uniqueString(8) . '.' . $ext;
                $file   = $this->ARCHIVES_DIR . $path;

                Entry::addUploadedFiles($path); // 新規バージョンとして作成する時にファイルをCOPYするかの判定に利用

                $imageFiles[] = array(
                    'edit'      => $Edit,
                    'target'    => $this->target,
                    'file'      => $file,
                    'path'      => $path,
                );
            }
        } while (false);

        return $imageFiles;
    }

    private function buildUpdateData()
    {
        $file       = null;
        $Edit       = array();
        $imageFiles = array();

        if (!empty($this->old) && ($xy = Storage::getImageSize($this->ARCHIVES_DIR . $this->old))) {
            $longSide   = max($xy[0], $xy[1]);

            if (!empty($this->size)) {
                $Edit['normal']['size'] = $this->size;
            } elseif (!empty($this->angle)) {
                $Edit['normal']['size'] = $longSide;
            }

            $Edit['tiny']['size'] = $this->tinySize;

            if (!empty($this->angle)) {
                $Edit['tiny']['angle'] = $this->angle;

                if ($this->squareSize > 0) {
                    $Edit['square']['size']     = $this->squareSize;
                    $Edit['square']['angle']    = $this->angle;
                }
                $Edit['normal']['angle'] = $this->angle;
            }

            if (!empty($Edit)) {
                $path   = $this->old;
                $file   = $this->ARCHIVES_DIR . $this->old;
                $this->target = $file;
                $large  = preg_replace('@(.*/)([^/]*)$@', '$1large-$2', $this->old);

                if (Storage::getImageSize($this->ARCHIVES_DIR . $large)) {
                    if (!empty($this->angle)) {
                        $Edit['large']['size']  = $this->largeSize;
                        $Edit['large']['angle'] = $this->angle;
                        if (empty($this->size)) {
                            $xy = Storage::getImageSize($file);
                            $Edit['normal']['size'] = max($xy[0], $xy[1]);
                        }
                    }
                    $this->target = $this->ARCHIVES_DIR . $large;
                }
            }

            if (!empty($this->angle)) {
                $this->deleteExtensionImage($this->ARCHIVES_DIR . $this->old);
            }

            if (
                1
                && $this->edit === 'none'
                && $this->selectSize === $this->oldSize
            ) {
                $file = null;
            }
            $imageFiles[] = array(
                'edit'      => $Edit,
                'target'    => $this->target,
                'file'      => $file,
                'path'      => $path,
            );
        }

        return $imageFiles;
    }

    private function editAndSaveImage($imageFiles = array())
    {
        foreach ($imageFiles as $k => $imageEdit) {
            if (!empty($imageEdit['target']) && !empty($imageEdit['file'])) {
                foreach (array('tiny', 'square', 'normal', 'large') as $type_) {
                    if (!isset($imageEdit['edit'][$type_])) {
                        continue;
                    }
                    $label  = $type_;
                    $to     = $imageEdit['edit'][$type_];

                    $pfx    = ('normal' == $label) ? '' : $label . '-';
                    $_file  = preg_replace('@(.*/)([^/]*)$@', '$1' . $pfx . '$2', $imageEdit['file']);
                    if (!preg_match('@\.([^.]+)$@', $_file, $match)) {
                        continue;
                    }
                    $ext    = $match[1];

                    $_size  = !empty($to['size']) ? $to['size'] : null;
                    $_angle = !empty($to['angle']) ? $to['angle'] : null;

                    ///* [CMS-762] (2).引き継いできたsizeを、指定があれば特定の辺に適用
                    $_width = null;
                    $_height = null;

                    // width
                    if (
                        0
                        || ($label === 'normal' && ($this->stdSide      === 'w' || $this->stdSide      === 'width'))
                        || ($label === 'tiny'   && ($this->stdSideTiny  === 'w' || $this->stdSideTiny  === 'width'))
                        || ($label === 'large'  && ($this->stdSideLarge === 'w' || $this->stdSideLarge === 'width'))
                    ) {
                        $_width = $_size;
                        $_size  = null;
                    }
                    // height
                    if (
                        0
                        || ($label === 'normal' && ($this->stdSide      === 'h' || $this->stdSide      === 'height'))
                        || ($label === 'tiny'   && ($this->stdSideTiny  === 'h' || $this->stdSideTiny  === 'height'))
                        || ($label === 'large'  && ($this->stdSideLarge === 'h' || $this->stdSideLarge === 'height'))
                    ) {
                        $_height = $_size;
                        $_size   = null;
                    }
                    // square
                    if ($label === 'square') {
                        $_width  = $_size;
                        $_height = $_size;
                    }

                    $editTarget = $imageEdit['target'];

                    Image::resizeImg($editTarget, $_file, $ext, $_width, $_height, $_size, $_angle);

                    if (HOOK_ENABLE) {
                        $Hook = ACMS_Hook::singleton();
                        $Hook->call('mediaCreate', $_file);
                    }
                }
            }
        }
    }

    private function deleteImage()
    {
        if (Entry::isNewVersion()) {
            return;
        }
        if (!empty($this->delete)) {
            if (empty($this->target)) {
                $path = null;
            }
            if (Storage::isFile($this->delete)) {
                $name   = Storage::mbBasename($this->delete);
                $dir    = substr($this->delete, 0, (strlen($this->delete) - strlen($name)));
                if ($this->olddel === true) {
                    $this->deleteExtensionImage($this->delete);

                    Storage::remove($this->delete);
                    Storage::remove($dir . 'tiny-' . $name);
                    Storage::remove($dir . 'large-' . $name);
                    Storage::remove($dir . 'square-' . $name);

                    Storage::remove($this->delete . '.webp');
                    Storage::remove($dir . 'tiny-' . $name . '.webp');
                    Storage::remove($dir . 'large-' . $name . '.webp');
                    Storage::remove($dir . 'square-' . $name . '.webp');

                    if (HOOK_ENABLE) {
                        $Hook = ACMS_Hook::singleton();
                        $Hook->call('mediaDelete', $this->delete);
                        $Hook->call('mediaDelete', $dir . 'tiny-' . $name);
                        $Hook->call('mediaDelete', $dir . 'large-' . $name);
                        $Hook->call('mediaDelete', $dir . 'square-' . $name);

                        $Hook->call('mediaDelete', $this->delete . '.webp');
                        $Hook->call('mediaDelete', $dir . 'tiny-' . $name . '.webp');
                        $Hook->call('mediaDelete', $dir . 'large-' . $name . '.webp');
                        $Hook->call('mediaDelete', $dir . 'square-' . $name . '.webp');
                    }
                }
            }
        }
    }

    private function deleteExtensionImage($path)
    {
        if (!Storage::isFile($path)) {
            return false;
        }

        $name = Storage::mbBasename($path);
        $dir = substr($path, 0, (strlen($path) - strlen($name)));

        $images = glob($dir . '*-' . $name);
        if (is_array($images)) {
            foreach ($images as $filename) {
                if (preg_match('/(tiny|large|square)-(.*)$/', $filename)) {
                    continue;
                }
                Storage::remove($filename);
                if (HOOK_ENABLE) {
                    $Hook = ACMS_Hook::singleton();
                    $Hook->call('mediaDelete', $filename);
                }
            }
        }
    }

    /**
     * 画像サイズの情報を設定する
     *
     * @param string $size 画像サイズ ex: width820:acms-col-sm-12
     */
    private function buildSize($size)
    {
        $tinySize     = config('image_size_tiny');
        $largeSize    = config('image_size_large');
        $squareSize   = intval(config('image_size_square'));

        // normal
        if (preg_match('/^(w|width|h|height)(\d+)/', $size, $matches)) {
            $this->stdSide  = strval($matches[1]);
            $this->size     = intval($matches[2]);
        } else {
            $this->size     = intval($size);
        }
        // tiny
        if (preg_match('/^(w|width|h|height)(\d+)/', $tinySize, $matches)) {
            $this->stdSideTiny  = strval($matches[1]);
            $this->tinySize     = intval($matches[2]);
        } else {
            $this->tinySize     = intval($tinySize);
        }
        // large
        if (preg_match('/^(w|width|h|height)(\d+)/', $largeSize, $matches)) {
            $this->stdSideLarge   = strval($matches[1]);
            $this->largeSize      = intval($matches[2]);
        } else {
            $this->largeSize      = intval($largeSize);
        }
        // square
        if ($squareSize < 1) {
            $this->squareSize = -1;
        } else {
            $this->squareSize = $squareSize;
        }

        if ($this->size !== 0 and $this->size < $this->tinySize) {
            $this->tinySize = $this->size;
        }
    }
}
