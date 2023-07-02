<?php

namespace Acms\Services\Image;

use Storage;
use Imagick;
use ACMS_Hook;
use ImageOptimizer\OptimizerFactory;

class Helper
{
    protected $exts = array(
        'image/gif'         => 'gif',
        'image/png'         => 'png',
        'image/vnd.wap.wbmp'=> 'bmp',
        'image/xbm'         => 'xbm',
        'image/jpeg'        => 'jpg',
    );

    /**
     * @var \ImageOptimizer\SmartOptimizer
     */
    protected $optimizer;

    public function __construct()
    {
        $factory = new OptimizerFactory(array('ignore_errors' => false));
        $this->optimizer = $factory->get();
    }

    /**
     * ロスレス圧縮
     *
     * @param $path
     */
    public function optimize($path)
    {
        if ( config('img_optimizer') === 'off' ) {
            return;
        }
        try {
            if ($this->optimizeTest($path)) {
                $this->optimizer->optimize($path);
            }
        } catch (\Exception $e) {}
    }

    /**
     * ロスレス圧縮可能かテスト
     *
     * @param $path
     * @return bool
     */
    public function optimizeTest($path)
    {
        try {
            if (Storage::isWritable($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $test = ARCHIVES_DIR . uniqueString() . '.' . $ext;
                @copy($path, $test);
                $this->optimizer->optimize($test);
                $size = @filesize($test);
                Storage::remove($test);
                if (empty($size)) {
                    return false;
                }
                return true;
            }
        } catch (\Exception $e) {
            Storage::remove($test);
        }
        return false;
    }

    /**
     * 画像の複製
     *
     * @param string $from
     * @param string $to
     * @param int $width
     * @param int $height
     * @param int $size
     * @param int $angle
     *
     * @return bool
     */
    public function copyImage($from, $to, $width=null, $height=null, $size=null, $angle=null)
    {
        if ( !($xy = Storage::getImageSize($from)) ) { return false; }
        if ( !Storage::makeDirectory(dirname($to)) ) { return false; }

        $xy['size'] = max($xy[0], $xy[1]);

        //----------------
        // fromExt, toExt
        if ( !isset($this->exts[$xy['mime']]) ) { return false; }
        $fromExt = $this->exts[$xy['mime']];
        $toExt = $fromExt;
        if ( preg_match('@\.([^.]+)$@u', $to, $match) ) { $toExt  = $match[1]; }

        //--------
        // resize
        $fromFunc   = array(
            'gif'   => 'imagecreatefromgif',
            'png'   => 'imagecreatefrompng',
            'bmp'   => 'imagecreatefromwbmp',
            'xbm'   => 'imagecreatefromxbm',
            'jpg'   => 'imagecreatefromjpeg',
        );

        $toFunc = array(
            'gif'   => 'imagegif',
            'png'   => 'imagepng',
            'bmp'   => 'imagewbmp',
            'xbm'   => 'imagexbm',
        );
        if ( 0
            or !empty($width) and $width < $xy[0]
            or !empty($height) and $height < $xy[1]
            or !empty($size) and $size < $xy['size']
            or !empty($angle)
            or $fromExt <> $toExt
        ) {
            if ( class_exists('Imagick') && config('image_magick') == 'on' ) {
                $this->editImageForImagick($from, $to, $width, $height, $size, $angle);
                $this->createWebpWithImagick($to, $to . '.webp');
            } else if (empty($toFunc[$toExt])) {
                $resource = $this->editImage($fromFunc[$fromExt]($from), $width, $height, $size, $angle);
                $imageQuality = intval(config('image_jpeg_quality'));
                imagejpeg($resource, $to, $imageQuality);
                $this->createWebpWithGd($resource, $to . '.webp', $imageQuality);
            } else {
                $resource = $this->editImage($fromFunc[$fromExt]($from), $width, $height, $size, $angle);
                $toFunc[$toExt]($resource, $to);
                $this->createWebpWithGd($resource, $to . '.webp', intval(config('image_jpeg_quality')));
            }
        //----------
        // raw copy
        } else {
            if (empty($toFunc[$toExt])) {
                imagejpeg(imagecreatefromjpeg($from), $to, config('image_jpeg_quality'));
                $this->createWebpWithGd(imagecreatefromjpeg($from), $to . '.webp', config('image_jpeg_quality'));
            } else {
                Storage::copy($from, $to);
            }
        }

        $this->optimize($to);
        Storage::changeMod($to);

        if ( HOOK_ENABLE ) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('mediaCreate', $to);
        }
        return true;
    }

    /**
     * @param $srcPath
     * @param $distPath
     * @param $ext
     * @param null $width
     * @param null $height
     * @param null $size
     * @param null $angle
     * @throws \ImagickException
     */
    public function resizeImg($srcPath, $distPath, $ext, $width=null, $height=null, $size=null, $angle=null)
    {
        $imageQuality = intval(config('image_jpeg_quality'));

        if (class_exists('Imagick') && config('image_magick') == 'on') {
            $this->editImageForImagick($srcPath, $distPath, $width, $height, $size, $angle);
            $this->createWebpWithImagick($distPath, $distPath . '.webp');
        } else if ('gif' == $ext) {
            imagegif($this->editImage(
                imagecreatefromgif($srcPath), $width, $height, $size, $angle
            ), $distPath);
        } else if ('png' == $ext) {
            $resource = $this->editImage(imagecreatefrompng($srcPath), $width, $height, $size, $angle);
            imagepng($resource, $distPath);
            $this->createWebpWithGd($resource, $distPath . '.webp');
        } else if ('bmp' == $ext) {
            imagewbmp($this->editImage(
                imagecreatefromwbmp($srcPath), $width, $height, $size, $angle
            ), $distPath);
        } else if ('xbm' == $ext) {
            imagexbm($this->editImage(
                imagecreatefromxbm($srcPath), $width, $height, $size, $angle
            ), $distPath);
        } else {
            $resource = $this->editImage(imagecreatefromjpeg($srcPath), $width, $height, $size, $angle);
            imagejpeg($resource, $distPath, $imageQuality);
            $this->createWebpWithGd($resource, $distPath . '.webp');
        }
        $this->optimize($distPath);
        Storage::changeMod($distPath);
    }

    /**
     * @return bool
     */
    public function isAvailableWebpWithGd()
    {
        static $available = null;
        if ($available !== null) return $available;

        if (!function_exists('imagewebp')) {
            $available = false;
            return false;
        }
        if (config('webp_support') !== 'on') {
            $available = false;
            return false;
        }
        foreach (gd_info() as $type => $support) {
            if (mb_strtolower($type) === 'webp support' && $support) {
                $available = true;
                return true;
            }
        }
        $available = false;
        return false;
    }

    /**
     * @return bool
     */
    public function isAvailableWebpWithImagick()
    {
        static $available = null;
        if ($available !== null) return $available;

        if (!class_exists('Imagick')) {
            $available = false;
            return false;
        }
        if (config('webp_support') !== 'on') {
            $available = false;
            return false;
        }
        $formats = Imagick::queryformats();
        foreach ($formats as $format) {
            if (mb_strtolower($format) === 'webp') {
                $available = true;
                return true;
            }
        }
        $available = false;
        return false;
    }

    /**
     * @param $resource
     * @param $distPath
     * @param int $imageQuality
     */
    public function createWebpWithGd($resource, $distPath, $imageQuality=75)
    {
        if (!$this->isAvailableWebpWithGd()) {
            return;
        }
        if (is_resource($resource) && 'gd' === get_resource_type($resource)
            || is_object($resource) && $resource instanceof \GdImage
        ) {
            imagewebp($resource, $distPath, $imageQuality);
        } else if ($resource) {
            $fromFunc = [
                'gif' => 'imagecreatefromgif',
                'png' => 'imagecreatefrompng',
                'bmp' => 'imagecreatefromwbmp',
                'xbm' => 'imagecreatefromxbm',
                'jpg' => 'imagecreatefromjpeg',
            ];
            if ($imageInfo = Storage::getImageSize($resource)) {
                $fromExt = $this->exts[$imageInfo['mime']];
                if (isset($fromFunc[$fromExt])) {
                    $resource = $fromFunc[$fromExt]($resource);
                    imagewebp($resource, $distPath, $imageQuality);
                }
            }
        }
    }

    /**
     * @param $srcPath
     * @param $distPath
     * @param int $imageQuality
     * @throws \ImagickException
     */
    public function createWebpWithImagick($srcPath, $distPath, $imageQuality=75)
    {
        if (!$this->isAvailableWebpWithImagick()) {
            return;
        }
        $imagick = new Imagick($srcPath);
        $imagick->implodeImage(0.0001);
        $imagick->setImageCompressionQuality($imageQuality);
        $imagick->setFormat('webp');
        $imagick->writeImages($distPath, true);
        $imagick->destroy();
    }

    /**
     * 画像のリサイズ（GD使用）
     *
     * @param resource $rsrc
     * @param int $width
     * @param int $height
     * @param int $size
     * @param int $angle
     *
     * @return resource
     */
    public function editImage($rsrc, $width=null, $height=null, $size=null, $angle=null)
    {
        $x          = imagesx($rsrc);
        $y          = imagesy($rsrc);
        $longSide   = max($x, $y);
        $ratio      = null;
        $coordinateX = 0;
        $coordinateY = 0;

        if ( !empty($width) and !empty($height) and !empty($size) ) {
            if ( $size < $longSide ) {
                $nx     = $size;
                $ny     = $size;
                if ( $x > $y ) {
                    $coordinateX = ceil(($x - $y) / 2);
                    $x = $y;
                } else {
                    $coordinateY = ceil(($y - $x) / 2);
                    $y = $x;
                }
            } else {
                if ( $x > $y ) {
                    $nx     = $y;
                    $ny     = $y;
                    $coordinateX = ceil(($x - $y) / 2);
                    $x = $y;
                } else {
                    $nx     = $x;
                    $ny     = $x;
                    $coordinateY = ceil(($y - $x) / 2);
                    $y = $x;
                }
            }

        } else if ( !empty($width) and $width < $x ) {
            $ratio  = $width / $x;
            $nx     = $width;
            $ny     = ceil($y * $ratio);

        } else if ( !empty($height) and $height < $y ) {
            $ratio  = $height / $y;
            $nx     = ceil($x * $ratio);
            $ny     = $height;

        } else if ( !empty($size) and $size < $longSide ) {
            $ratio  = $size / $longSide;
            $nx     = ceil($x * $ratio);
            $ny     = ceil($y * $ratio);

        } else {
            $nx     = $x;
            $ny     = $y;
        }

        //--------------
        // tranceparent
        $nrsrc  = imagecreatetruecolor($nx, $ny);

        if ( 0 <= ($idx = imagecolortransparent($rsrc)) ) {
            @imagetruecolortopalette($nrsrc, true, 256);
            $rgb    = @imagecolorsforindex($rsrc, $idx);
            $idx    = imagecolorallocate($nrsrc, $rgb['red'], $rgb['green'], $rgb['blue']);
            imagefill($nrsrc, 0, 0, $idx);
            imagecolortransparent($nrsrc, $idx);
        } else {
            imagealphablending($nrsrc, false);
            imagefill($nrsrc, 0, 0, imagecolorallocatealpha($nrsrc, 0, 0, 0, 127));
            imagesavealpha($nrsrc, true);
        }
        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($nrsrc); // true color に変換
        }
        imagecopyresampled($nrsrc, $rsrc, 0, 0, $coordinateX, $coordinateY, $nx, $ny, $x, $y);

        if (function_exists('imagerotate') and ($angle = intval($angle))) {
            $nrsrc = imagerotate($nrsrc, $angle, 0);
        }

        // シャープネス
        // if (function_exists('imageconvolution')) {
        //     $filter = array(
        //         array( 0.0, -1.0, 0.0 ),
        //         array( -1.0, 5.5, -1.0 ),
        //         array( 0.0, -1.0, 0.0 )
        //     );
        //     $div = array_sum(array_map('array_sum', $filter));
        //     imageconvolution($nrsrc, $filter, $div, 0);
        // }

        return $nrsrc;
    }

    /**
     * 画像のリサイズ（Image Magic使用）
     *
     * @param resource $rsrc
     * @param string $file
     * @param int $width
     * @param int $height
     * @param int $size
     * @param int $angle
     *
     * @return resource
     * @throws
     */
    public function editImageForImagick($rsrc, $file, $width=null, $height=null, $size=null, $angle=null)
    {
        $imagick    = new Imagick($rsrc);
        $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality(intval(config('image_jpeg_quality')));
        // $imagick->sharpenimage(0.8, 0.6); // シャープネス
        $imageprops = $imagick->getImageGeometry();

        $x          = $imageprops['width'];
        $y          = $imageprops['height'];
        $longSide   = max($x, $y);
        $ratio      = null;

        $coordinateX = 0;
        $coordinateY = 0;

        // square image
        if ( !empty($width) and !empty($height) and !empty($size) ) {
            if ( $size < $longSide ) {
                $nx     = $size;
                $ny     = $size;
                // landscape
                if ( $x > $y ) {
                    $coordinateX = ceil(($x - $y) / 2);
                    $x = $y;
                // portrait
                } else {
                    $coordinateY = ceil(($y - $x) / 2);
                    $y = $x;
                }
            } else {
                // landscape
                if ( $x > $y ) {
                    $nx     = $y;
                    $ny     = $y;
                    $coordinateX = ceil(($x - $y) / 2);
                    $x = $y;
                // protrait
                } else {
                    $nx     = $x;
                    $ny     = $x;
                    $coordinateY = ceil(($y - $x) / 2);
                    $y = $x;
                }
            }
        // normal, tiny, large
        } else if ( !empty($width) and $width < $x ) {
            $ratio  = $width / $x;
            $nx     = $width;
            $ny     = ceil($y * $ratio);

        } else if ( !empty($height) and $height < $y ) {
            $ratio  = $height / $y;
            $nx     = ceil($x * $ratio);
            $ny     = $height;

        } else if ( !empty($size) and $size < $longSide ) {
            $ratio  = $size / $longSide;
            $nx     = ceil($x * $ratio);
            $ny     = ceil($y * $ratio);

        } else {
            $nx     = $x;
            $ny     = $y;
        }

        //--------------
        // tranceparent
        $imagick->cropImage($x, $y, $coordinateX, $coordinateY);
        $imagick->resizeImage($nx, $ny, Imagick::FILTER_LANCZOS, 0.9, true);

        //--------
        // rotate
        if ( $angle = intval($angle) ) {
            $imagick->rotateImage('none', -1*$angle);
        }

        $imagick->writeImages($file, true);
        $imagick->destroy();
    }

    /**
     * 全サイズの画像削除
     *
     * @param string $path
     *
     * @return void
     */
    public function deleteImageAllSize($path)
    {
        if ( $dirname = dirname($path) ) { $dirname .= '/'; }
        $basename   = Storage::mbBasename($path);
        Storage::remove($dirname.$basename);
        Storage::remove($dirname.'tiny-'.$basename);
        Storage::remove($dirname.'large-'.$basename);
        Storage::remove($dirname.'square-'.$basename);
        Storage::remove($dirname.'square64-'.$basename);

        Storage::remove($dirname.$basename.'.webp');
        Storage::remove($dirname.'tiny-'.$basename.'.webp');
        Storage::remove($dirname.'large-'.$basename.'.webp');
        Storage::remove($dirname.'square-'.$basename.'.webp');
        Storage::remove($dirname.'square64-'.$basename.'.webp');

        $images = glob($dirname.'*-'.$basename.'*');
        if ( is_array($images) ) {
            foreach ( $images as $filename ) {
                Storage::remove($filename);
                if (HOOK_ENABLE) {
                    $Hook = ACMS_Hook::singleton();
                    $Hook->call('mediaDelete', $filename);
                }
            }
        }

        if ( HOOK_ENABLE ) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('mediaDelete', $dirname.$basename);
            $Hook->call('mediaDelete', $dirname.'tiny-'.$basename);
            $Hook->call('mediaDelete', $dirname.'large-'.$basename);
            $Hook->call('mediaDelete', $dirname.'square-'.$basename);

            $Hook->call('mediaDelete', $dirname.$basename.'.webp');
            $Hook->call('mediaDelete', $dirname.'tiny-'.$basename.'.webp');
            $Hook->call('mediaDelete', $dirname.'large-'.$basename.'.webp');
            $Hook->call('mediaDelete', $dirname.'square-'.$basename.'.webp');
        }
    }

    /**
     * mime type から拡張子の取得
     *
     * @param string $target_mime
     *
     * @return string
     */
    public function detectImageExtenstion($target_mime)
    {
        foreach ( $this->exts as $mime => $extension ) {
            if ( $mime == $target_mime ) {
                return $extension;
            }
        }
        return '';
    }
}
