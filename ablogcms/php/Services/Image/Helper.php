<?php

namespace Acms\Services\Image;

use Imagick;
use ACMS_Hook;
use ImageOptimizer\OptimizerFactory;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Storage;

class Helper
{
    protected $exts = [
        'image/gif'          => 'gif',
        'image/png'          => 'png',
        'image/vnd.wap.wbmp' => 'bmp',
        'image/xbm'          => 'xbm',
        'image/jpeg'         => 'jpg',
    ];

    /**
     * @var \ImageOptimizer\Optimizer
     */
    protected $optimizer = null;

    public function __construct()
    {
        if (config('img_optimizer') !== 'off') {
            $factory = new OptimizerFactory(['ignore_errors' => false]);
            $this->optimizer = $factory->get();
        }
    }

    /**
     * ロスレス圧縮
     *
     * @param string $path
     */
    public function optimize($path)
    {
        if ($this->optimizer === null) {
            return;
        }
        try {
            if ($this->optimizeTest($path)) {
                $this->optimizer->optimize($path);
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * ロスレス圧縮可能かテスト
     *
     * @param string $path
     * @return bool
     */
    public function optimizeTest($path)
    {
        if ($this->optimizer === null) {
            return false;
        }
        $test = null;
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
            if ($test !== null) {
                Storage::remove($test);
            }
        }
        return false;
    }

    /**
     * 画像の複製
     *
     * @param string $from
     * @param string $to
     * @param int|null $width
     * @param int|null $height
     * @param int|null $size
     * @param int|null $angle
     *
     * @return bool
     */
    public function copyImage($from, $to, $width = null, $height = null, $size = null, $angle = null)
    {

        /**
         * @var array{
         *  0: int,
         *  1: int,
         *  2: int,
         *  3: string,
         *  bits: int,
         *  channels: int,
         *  mime: string
         * }|false $xy
         */
        $xy = Storage::getImageSize($from);
        if ($xy === false) {
            return false;
        }
        if (!Storage::makeDirectory(dirname($to))) {
            return false;
        }

        $xy['size'] = max($xy[0], $xy[1]);

        //----------------
        // fromExt, toExt
        if (!isset($this->exts[$xy['mime']])) {
            return false;
        }
        $fromExt = $this->exts[$xy['mime']];
        $toExt = $fromExt;
        if (preg_match('@\.([^.]+)$@u', $to, $match)) {
            $toExt  = $match[1];
        }

        //--------
        // resize
        $fromFunc   = [
            'gif'   => 'imagecreatefromgif',
            'png'   => 'imagecreatefrompng',
            'bmp'   => 'imagecreatefromwbmp',
            'xbm'   => 'imagecreatefromxbm',
            'jpg'   => 'imagecreatefromjpeg',
        ];

        $toFunc = [
            'gif'   => 'imagegif',
            'png'   => 'imagepng',
            'bmp'   => 'imagewbmp',
            'xbm'   => 'imagexbm',
        ];
        if (
            0
            or !empty($width) and $width < $xy[0]
            or !empty($height) and $height < $xy[1]
            or !empty($size) and $size < $xy['size']
            or !empty($angle)
            or $fromExt <> $toExt
        ) {
            if (class_exists('Imagick') && config('image_magick') == 'on') {
                $this->editImageForImagick($from, $to, $width, $height, $size, $angle);
                $this->createWebpWithImagick($to, $to . '.webp');
            } elseif (empty($toFunc[$toExt])) {
                $resource = $this->editImage($fromFunc[$fromExt]($from), $width, $height, $size, $angle);
                $imageQuality = intval(config('image_jpeg_quality', 75));
                imagejpeg($resource, $to, $imageQuality);
                $this->createWebpWithGd($resource, $to . '.webp', $imageQuality);
            } else {
                $resource = $this->editImage($fromFunc[$fromExt]($from), $width, $height, $size, $angle);
                $toFunc[$toExt]($resource, $to);
                $this->createWebpWithGd($resource, $to . '.webp', intval(config('image_jpeg_quality', 75)));
            }
            //----------
            // raw copy
        } else {
            if (empty($toFunc[$toExt])) {
                imagejpeg(imagecreatefromjpeg($from), $to, config('image_jpeg_quality', 75));
                $this->createWebpWithGd(imagecreatefromjpeg($from), $to . '.webp', config('image_jpeg_quality', 75));
            } else {
                Storage::copy($from, $to);
            }
        }

        $this->optimize($to);
        Storage::changeMod($to);

        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('mediaCreate', $to);
        }
        return true;
    }

    /**
     * @param string $srcPath
     * @param string $distPath
     * @param string $ext
     * @param int|null $width
     * @param int|null $height
     * @param int|null $size
     * @param int|null $angle
     * @throws \ImagickException
     */
    public function resizeImg($srcPath, $distPath, $ext, $width = null, $height = null, $size = null, $angle = null)
    {
        $imageQuality = intval(config('image_jpeg_quality', 75));

        if (class_exists('Imagick') && config('image_magick') == 'on') {
            $this->editImageForImagick($srcPath, $distPath, $width, $height, $size, $angle);
            $this->createWebpWithImagick($distPath, $distPath . '.webp');
        } elseif ('gif' == $ext) {
            imagegif($this->editImage(
                imagecreatefromgif($srcPath),
                $width,
                $height,
                $size,
                $angle
            ), $distPath);
        } elseif ('png' == $ext) {
            $resource = $this->editImage(imagecreatefrompng($srcPath), $width, $height, $size, $angle);
            imagepng($resource, $distPath);
            $this->createWebpWithGd($resource, $distPath . '.webp');
        } elseif ('bmp' == $ext) {
            imagewbmp($this->editImage(
                imagecreatefromwbmp($srcPath),
                $width,
                $height,
                $size,
                $angle
            ), $distPath);
        } elseif ('xbm' == $ext) {
            imagexbm($this->editImage(
                imagecreatefromxbm($srcPath),
                $width,
                $height,
                $size,
                $angle
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
        if ($available !== null) {
            return $available;
        }

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
        if ($available !== null) {
            return $available;
        }

        if (!class_exists('Imagick')) {
            $available = false;
            return false;
        }
        if (config('webp_support') !== 'on') {
            $available = false;
            return false;
        }
        $formats = Imagick::queryFormats();
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
     * @param string|resource|\GdImage $resource
     * @param string $distPath
     * @param int $imageQuality
     * @return void
     */
    public function createWebpWithGd($resource, $distPath, $imageQuality = 75): void // @phpstan-ignore-line
    {
        if (!$this->isAvailableWebpWithGd()) {
            return;
        }

        if (is_string($resource)) {
            // パス指定の場合はリソースに変換
            if ($resource === '') {
                throw new \InvalidArgumentException('Resource is empty.');
            }

            if (!Storage::isReadable($resource)) {
                throw new \InvalidArgumentException('Resource is not readable.');
            }

            /**
             * @var array{
             *  0: int,
             *  1: int,
             *  2: int,
             *  3: string,
             *  bits: int,
             *  channels: int,
             *  mime: string
             * }|false $imageInfo
             * */
            $imageInfo = Storage::getImageSize($resource);

            if (!$imageInfo) {
                throw new \RuntimeException('Failed to get image info.');
            }

            $fromFunc = [
                'gif' => 'imagecreatefromgif',
                'png' => 'imagecreatefrompng',
                'bmp' => 'imagecreatefromwbmp',
                'xbm' => 'imagecreatefromxbm',
                'jpg' => 'imagecreatefromjpeg',
            ];

            $fromExt = $this->exts[$imageInfo['mime']];
            if (!isset($fromFunc[$fromExt])) {
                throw new \RuntimeException('Unsupported image type.');
            }

            /** @var \GdImage|resource|false $resource */
            $resource = $fromFunc[$fromExt]($resource); // @phpstan-ignore-line

            if (!$resource) {
                throw new \RuntimeException('Failed to create image resource.');
            }
        }

        // パレットベースの画像かどうかを確認し、TrueColorに変換
        if (!imageistruecolor($resource)) {
            // パレットベースの画像をTrueColorに変換
            imagepalettetotruecolor($resource);
        }

        // PNG画像の場合、TrueColorに変換後に透明度情報を保持するために必要
        // PNG画像でない場合は適用しても何も起こらない
        imagealphablending($resource, false);
        imagesavealpha($resource, true);

        if (imagewebp($resource, $distPath, $imageQuality)) {
            Storage::changeMod($distPath);
        } else {
            Logger::error('GDによるWebP画像の生成に失敗しました', [
                'distPath' => $distPath,
                'imageQuality' => $imageQuality,
            ]);
            throw new \RuntimeException('Failed to create webp with GD.');
        }
    }

    /**
     * @param $srcPath
     * @param $distPath
     * @param int $imageQuality
     * @throws \ImagickException
     */
    public function createWebpWithImagick($srcPath, $distPath, $imageQuality = 75)
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

        Storage::changeMod($distPath);
    }

    /**
     * 画像のリサイズ（GD使用）
     *
     * @param resource|\GdImage $rsrc
     * @param int|null $width
     * @param int|null $height
     * @param int|null $size
     * @param int|null $angle
     *
     * @return resource|\GdImage
     */
    public function editImage($rsrc, $width = null, $height = null, $size = null, $angle = null) // @phpstan-ignore-line
    {
        $x          = imagesx($rsrc);
        $y          = imagesy($rsrc);
        $longSide   = max($x, $y);
        $ratio      = null;
        $coordinateX = 0;
        $coordinateY = 0;

        if (!empty($width) and !empty($height) and !empty($size)) {
            if ($size < $longSide) {
                $nx     = $size;
                $ny     = $size;
                if ($x > $y) {
                    $coordinateX = ceil(($x - $y) / 2);
                    $x = $y;
                } else {
                    $coordinateY = ceil(($y - $x) / 2);
                    $y = $x;
                }
            } else {
                if ($x > $y) {
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
        } elseif (!empty($width) and $width < $x) {
            $ratio  = $width / $x;
            $nx     = $width;
            $ny     = ceil($y * $ratio);
        } elseif (!empty($height) and $height < $y) {
            $ratio  = $height / $y;
            $nx     = ceil($x * $ratio);
            $ny     = $height;
        } elseif (!empty($size) and $size < $longSide) {
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

        if (0 <= ($idx = imagecolortransparent($rsrc))) {
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
     * @param string $rsrc
     * @param string $file
     * @param int|null $width
     * @param int|null $height
     * @param int|null $size
     * @param int|null $angle
     *
     * @return void
     * @throws \ImagickException
     */
    public function editImageForImagick($rsrc, $file, $width = null, $height = null, $size = null, $angle = null)
    {
        $imagick    = new Imagick($rsrc);
        $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality(intval(config('image_jpeg_quality', 75)));
        // $imagick->sharpenimage(0.8, 0.6); // シャープネス
        $imageprops = $imagick->getImageGeometry();

        $x          = $imageprops['width'];
        $y          = $imageprops['height'];
        $longSide   = max($x, $y);
        $ratio      = null;

        $coordinateX = 0;
        $coordinateY = 0;

        // square image
        if (!empty($width) and !empty($height) and !empty($size)) {
            if ($size < $longSide) {
                $nx     = $size;
                $ny     = $size;
                // landscape
                if ($x > $y) {
                    $coordinateX = ceil(($x - $y) / 2);
                    $x = $y;
                    // portrait
                } else {
                    $coordinateY = ceil(($y - $x) / 2);
                    $y = $x;
                }
            } else {
                // landscape
                if ($x > $y) {
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
        } elseif (!empty($width) and $width < $x) {
            $ratio  = $width / $x;
            $nx     = $width;
            $ny     = ceil($y * $ratio);
        } elseif (!empty($height) and $height < $y) {
            $ratio  = $height / $y;
            $nx     = ceil($x * $ratio);
            $ny     = $height;
        } elseif (!empty($size) and $size < $longSide) {
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
        if ($angle = intval($angle)) {
            $imagick->rotateImage('none', -1 * $angle);
        }
        $imagick->stripImage();
        $imagick->writeImages($file, true);
        $imagick->clear();
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
        if ($dirname = dirname($path)) {
            $dirname .= '/';
        }
        $basename   = Storage::mbBasename($path);
        Storage::remove($dirname . $basename);
        Storage::remove($dirname . 'tiny-' . $basename);
        Storage::remove($dirname . 'large-' . $basename);
        Storage::remove($dirname . 'square-' . $basename);
        Storage::remove($dirname . 'square64-' . $basename);

        Storage::remove($dirname . $basename . '.webp');
        Storage::remove($dirname . 'tiny-' . $basename . '.webp');
        Storage::remove($dirname . 'large-' . $basename . '.webp');
        Storage::remove($dirname . 'square-' . $basename . '.webp');
        Storage::remove($dirname . 'square64-' . $basename . '.webp');

        $images = glob($dirname . '*-' . $basename . '*');
        if (is_array($images)) {
            foreach ($images as $filename) {
                Storage::remove($filename);
                if (HOOK_ENABLE) {
                    $Hook = ACMS_Hook::singleton();
                    $Hook->call('mediaDelete', $filename);
                }
            }
        }

        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('mediaDelete', $dirname . $basename);
            $Hook->call('mediaDelete', $dirname . 'tiny-' . $basename);
            $Hook->call('mediaDelete', $dirname . 'large-' . $basename);
            $Hook->call('mediaDelete', $dirname . 'square-' . $basename);

            $Hook->call('mediaDelete', $dirname . $basename . '.webp');
            $Hook->call('mediaDelete', $dirname . 'tiny-' . $basename . '.webp');
            $Hook->call('mediaDelete', $dirname . 'large-' . $basename . '.webp');
            $Hook->call('mediaDelete', $dirname . 'square-' . $basename . '.webp');
        }
    }

    /**
     * mime type から拡張子の取得
     *
     * @param string $target_mime
     *
     * @return 'gif' | 'png' | 'bmp' | 'xbm' | 'jpg' | ''
     */
    public function detectImageExtenstion($target_mime)
    {
        foreach ($this->exts as $mime => $extension) {
            if ($mime == $target_mime) {
                return $extension;
            }
        }
        return '';
    }

    /**
     * サイズ違い（tiny, square, large, normal）の画像を生成
     *
     * @param array{
     *   name: string,
     *   type: string,
     *   tmp_name: string,
     *   error: int,
     *   size: int
     * } $File $_FILES[$name] で取得したファイル情報
     * @param array{
     *   normal?: int,
     *   tiny?: int,
     *   large?: int,
     *   square?: int
     * } $sizes
     * @param string $destDir
     * @param bool $isRandomFileName
     * @param int|null $angle
     * @param bool $forceLarge
     * @return array{
     *  path: string,
     *  type: string,
     *  name: string,
     *  size: string
     * }
     */
    public function createImages(
        array $File,
        array $sizes,
        string $destDir,
        bool $isRandomFileName = true,
        ?int $angle = null,
        bool $forceLarge = false
    ): array {
        $config = $this->createCreateImagesConfig(
            $File,
            $sizes,
            $destDir,
            $isRandomFileName,
            $angle,
            $forceLarge
        );

        return $this->createResizedImages($config);
    }

    /**
     * @param array{
     *   name: string,
     *   type: string,
     *   tmp_name: string,
     *   error: int,
     *   size: int
     * } $File $_FILES[$name] で取得したファイル情報
     * @param array{
     *   normal?: int,
     *   tiny?: int,
     *   large?: int,
     *   square?: int
     * } $sizes
     * @param string $destDir
     * @param bool $isRandomFileName
     * @param int|null $angle
     * @param bool $forceLarge
     * @return array{
     *   edit: array{
     *     tiny: array{
     *       size: int,
     *       angle?: int,
     *       side?: 'w' | 'h' | 'width' | 'height'
     *     },
     *     square?: array{
     *       size: int,
     *       angle?: int|null,
     *     },
     *     normal: array{
     *       size: int,
     *       angle?: int,
     *       side?: 'w' | 'h' | 'width' | 'height'
     *     },
     *     large?: array{
     *       size: int,
     *       angle?: int,
     *       side?: 'w' | 'h' | 'width' | 'height'
     *     }
     *   },
     *   srcPath: string,
     *   destPath: string,
     *   path: string,
     *   ext: 'gif' | 'png' | 'bmp' | 'xbm' | 'jpg',
     *   fileName: string
     * }
     */
    protected function createCreateImagesConfig(
        array $File,
        array $sizes,
        string $destDir,
        bool $isRandomFileName = true,
        ?int $angle = null,
        bool $forceLarge = false
    ): array {
        $tempPath = $File['tmp_name'];

        if ($tempPath === '') {
            throw new \InvalidArgumentException('Uploaded image file not found.');
        }

        $path = '';
        $ext = '';
        $edit = [];

        $normalSize = isset($sizes['normal']) ? strval($sizes['normal']) : '640';
        $tinySize = isset($sizes['tiny']) ? strval($sizes['tiny']) : '280';
        $largeSize = isset($sizes['large']) ? strval($sizes['large']) : '1200';
        $squareSize = isset($sizes['square']) ? intval($sizes['square']) : 300;

        ///* [CMS-762] (1).辺(string)と、px値(int)に分解する
        $stdSide = null;
        $stdSideTiny = null;
        $stdSideLarge = null;

        // normal
        if (preg_match('/^(w|width|h|height)(\d+)/', $normalSize, $matches)) {
            $stdSide = strval($matches[1]);
            $normalSize = intval($matches[2]);
        } else {
            $normalSize = intval($normalSize);
        }
        // tiny
        if (preg_match('/^(w|width|h|height)(\d+)/', $tinySize, $matches)) {
            $stdSideTiny = strval($matches[1]);
            $tinySize = intval($matches[2]);
        } else {
            $tinySize = intval($tinySize);
        }
        // large
        if (preg_match('/^(w|width|h|height)(\d+)/', $largeSize, $matches)) {
            $stdSideLarge = strval($matches[1]);
            $largeSize = intval($matches[2]);
        } else {
            $largeSize = intval($largeSize);
        }

        if ($squareSize < 1) {
            $squareSize = -1;
        }

        if ($normalSize !== 0 && $normalSize < $tinySize) {
            $tinySize = $normalSize;
        }

        $fileName = $File['name'];

        /**
         * @var array{
         *  0: int,
         *  1: int,
         *  2: int,
         *  3: string,
         *  bits: int,
         *  channels: int,
         *  mime: string
         * }|false $imageInfo
         * */
        $imageInfo = Storage::getImageSize($tempPath);
        if (!$imageInfo) {
            throw new \RuntimeException('Failed to get image info.');
        }

        /** @var int $longSide */
        $longSide = max($imageInfo[0], $imageInfo[1]);
        $mime = $imageInfo['mime'];

        $edit['tiny'] = [
            'size'  => $tinySize,
            'angle' => $angle,
            'side'  => $stdSideTiny,
        ];

        if ($squareSize > 0) {
            $edit['square'] = [
                'size'  => $squareSize,
                'angle' => $angle,
            ];
        }

        $edit['normal'] = [
            'size'  => $normalSize,
            'angle' => $angle,
            'side'  => $stdSide,
        ];

        if ($forceLarge || (!empty($normalSize) && $longSide > $normalSize)) {
            $edit['large'] = [
                'size'  => ($longSide > $largeSize) ? $largeSize : $longSide,
                'angle' => $angle,
                'side'  => $stdSideLarge,
            ];
        }

        $archivesDir = Storage::archivesDir();

        Storage::makeDirectory($destDir . $archivesDir);
        $ext = $this->detectImageExtenstion($mime) ?: 'jpg';

        $fileNameParts = preg_split('/\./', $fileName);
        array_pop($fileNameParts);
        $fileName = implode('.', $fileNameParts);
        $fileName = preg_replace('/\s/u', '_', $fileName);
        if (preg_match('@^(large|tiny|square)@', $fileName)) {
            $fileName = 'img_' . $fileName;
        }
        if (!$isRandomFileName) {
            $path = $archivesDir . $fileName . '.' . $ext;
            $path = uniqueFilePath($path, $destDir);
        } else {
            $path = $archivesDir . uniqueString(8) . '.' . $ext;
        }
        $destPath = $destDir . $path;

        return [
            'edit' => $edit,
            'srcPath' => $tempPath,
            'destPath' => $destPath,
            'path' => $path,
            'ext' => $ext,
            'fileName' => $fileName,
        ];
    }

    /**
     * @param array{
     *   edit: array{
     *     tiny: array{
     *       size: int,
     *       angle?: int,
     *       side?: 'w' | 'h' | 'width' | 'height'
     *     },
     *     square?: array{
     *       size: int,
     *       angle?: int|null,
     *     },
     *     normal: array{
     *       size: int,
     *       angle?: int,
     *       side?: 'w' | 'h' | 'width' | 'height'
     *     },
     *     large?: array{
     *       size: int,
     *       angle?: int,
     *       side?: 'w' | 'h' | 'width' | 'height'
     *     }
     *   },
     *   srcPath: string,
     *   destPath: string,
     *   path: string,
     *   ext: 'gif' | 'png' | 'bmp' | 'xbm' | 'jpg',
     *   fileName: string
     * } $config
     * @return array{
     *  path: string,
     *  type: string,
     *  name: string,
     *  size: string
     * }
     */
    protected function createResizedImages(array $config): array
    {
        if ($config['srcPath'] === '') {
            throw new \RuntimeException('Source file path not found.');
        }

        if ($config['destPath'] === '') {
            throw new \RuntimeException('Destination file path not found.');
        }

        $normalSize = '';
        $angleSrc = false;
        $isOriginalUpload = false;

        foreach (array_keys($config['edit']) as $sizeType) {
            /** @var 'tiny'| 'square' | 'normal' | 'large' $sizeType */

            /**
             * @var array{
             *     size: int,
             *     angle?: int,
             *     side?: 'w' | 'h' | 'width' | 'height'
             *  } $editConfig
             */
            $editConfig = $config['edit'][$sizeType];

            $pfx = ('normal' === $sizeType) ? '' : $sizeType . '-';
            /** @var string $destPath */
            $destPath = preg_replace('@(.*/)([^/]*)$@', '$1' . $pfx . '$2', $config['destPath']);
            if (!preg_match('@\.([^.]+)$@', $destPath, $match)) {
                continue;
            }
            $ext = $match[1];

            $size = $editConfig['size'] > 0 ? $editConfig['size'] : null;
            $angle = !is_null($editConfig['angle']) ? $editConfig['angle'] : null;

            $width = null;
            $height = null;

            // width
            if (
                in_array($sizeType, ['normal', 'tiny', 'large'], true) &&
                in_array($editConfig['side'], ['w', 'width'], true)
            ) {
                $width = $size;
                $size  = null;
            }
            // height
            if (
                in_array($sizeType, ['normal', 'tiny', 'large'], true) &&
                in_array($editConfig['side'], ['h', 'height'], true)
            ) {
                $height = $size;
                $size = null;
            }

            // square
            if ($sizeType === 'square') {
                $width = $size;
                $height = $size;
            }

            // 回転された画像をさらに回転処理しないように
            if ($angleSrc) {
                $angle = null;
            }
            if (!$angleSrc && $config['srcPath'] === $destPath) {
                $angleSrc = true;
            }

            if (
                (is_null($width) && is_null($height) && is_null($size)) // オリジナルのアップロード画像
                || $isOriginalUpload && $sizeType === 'large'
            ) {
                if (is_uploaded_file($config['srcPath'])) {
                    Storage::copy($config['srcPath'], $destPath);
                    if (class_exists('Imagick') && config('image_magick') == 'on') {
                        $this->createWebpWithImagick($config['srcPath'], $destPath . '.webp');
                    } else {
                        if (in_array($ext, ['png', 'jpg'], true)) {
                            $this->createWebpWithGd($config['srcPath'], $destPath . '.webp');
                        }
                    }
                    $this->optimize($destPath);
                    $isOriginalUpload = true;
                }
            } else {
                $this->resizeImg($config['srcPath'], $destPath, $ext, $width, $height, $size, $angle);
            }
            if ($sizeType === 'normal') {
                /**
                 * @var array{
                 *  0: int,
                 *  1: int,
                 *  2: int,
                 *  3: string,
                 *  bits: int,
                 *  channels: int,
                 *  mime: string
                 * }|false $xy
                 * */
                $xy = Storage::getImageSize($destPath);
                $normalSize = $xy[0] . ' x ' . $xy[1];
            }
            if (HOOK_ENABLE) {
                $Hook = ACMS_Hook::singleton();
                $Hook->call('mediaCreate', $destPath);
            }
        }

        return [
            'path'  => $config['path'],
            'type'  => strtoupper($config['ext']),
            'name'  => $config['fileName'],
            'size'  => $normalSize,
        ];
    }
}
