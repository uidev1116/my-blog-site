<?php

use Acms\Services\Facades\Image;
use Acms\Services\Facades\Storage;

class ImageResize
{
    public const MIME_GIF = 1;
    public const MIME_PNG = 2;
    public const MIME_BMP = 3;
    public const MIME_XBM = 4;
    public const MIME_JPEG = 5;

    public const SCALE_TO_FILL = 1;        // 出力サイズにめいっぱい広げる
    public const SCALE_ASPECT_FIT = 2;     // aspect比を維持して、ちょうど入るようにする
    public const SCALE_ASPECT_FILL = 3;    // aspect比を維持して、めいっぱい広げる

    protected $engine;

    protected $srcImg;
    protected $destImg;

    protected $mimeType;
    protected $qualityJpeg = 95;

    protected $originalW;
    protected $originalH;

    protected $srcX = 0;
    protected $srcY = 0;
    protected $srcW;
    protected $srcH;

    protected $destX = 0;
    protected $destY = 0;
    protected $destW;
    protected $destH;

    protected $canvasW;
    protected $canvasH;

    protected $colorR = 0;
    protected $colorG = 0;
    protected $colorB = 0;

    protected $mode = self::SCALE_ASPECT_FILL;

    public function __construct($path)
    {
        if (!$xy = Storage::getImageSize($path)) {
            AcmsLogger::warning('画像が読み込めないため、リサイズできませんでした', [
                'path' => $path,
            ]);
            throw new Exception('Can\'t read image file');
        }
        $this->mimeType = $xy['mime'];

        if (class_exists('Imagick') && config('image_magick') == 'on') {
            $this->engine = 'imagick';
            $this->createSrcImageForImagick($path);
        } else {
            $this->engine = 'gd';
            $this->createSrcImage($path);
        }
    }

    private function getExtension()
    {
        $exts = [
            'image/gif' => self::MIME_GIF,
            'image/png' => self::MIME_PNG,
            'image/vnd.wap.wbmp' => self::MIME_BMP,
            'image/xbm' => self::MIME_XBM,
            'image/jpeg' => self::MIME_JPEG,
        ];
        return isset($exts[$this->mimeType]) ? $exts[$this->mimeType] : self::MIME_JPEG;
    }

    private function createSrcImage($path)
    {
        switch ($this->getExtension()) {
            case self::MIME_GIF:
                $this->srcImg = imagecreatefromgif($path);
                break;
            case self::MIME_PNG:
                $this->srcImg = imagecreatefrompng($path);
                break;
            case self::MIME_BMP:
                $this->srcImg = imagecreatefromwbmp($path);
                break;
            case self::MIME_XBM:
                $this->srcImg = imagecreatefromxbm($path);
                break;
            default:
                $this->srcImg = imagecreatefromjpeg($path);
                break;
        }
        $this->originalW = imagesx($this->srcImg);
        $this->originalH = imagesy($this->srcImg);
    }

    private function createSrcImageForImagick($path)
    {
        $this->srcImg = new Imagick($path);
        $this->originalW = $this->srcImg->getImageWidth();
        $this->originalH = $this->srcImg->getImageHeight();
    }

    private function createDestImage()
    {
        $this->destImg = imagecreatetruecolor($this->canvasW, $this->canvasH);

        if (0 <= ($idx = imagecolortransparent($this->srcImg))) {
            @imagetruecolortopalette($this->destImg, true, 256);
            $rgb = @imagecolorsforindex($this->srcImg, $idx);
            $idx = imagecolorallocate($this->destImg, $rgb['red'], $rgb['green'], $rgb['blue']);
            imagefill($this->destImg, 0, 0, $idx);
            imagecolortransparent($this->destImg, $idx);
        } else {
            imagealphablending($this->destImg, false);
            imagefill(
                $this->destImg,
                0,
                0,
                imagecolorallocatealpha($this->destImg, $this->colorR, $this->colorG, $this->colorB, 127)
            );
            imagesavealpha($this->destImg, true);
        }
        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($this->destImg); // true color に変換
        }
        imagecopyresampled(
            $this->destImg,
            $this->srcImg,
            $this->destX,
            $this->destY,
            $this->srcX,
            $this->srcY,
            $this->destW,
            $this->destH,
            $this->srcW,
            $this->srcH
        );
    }

    private function createDestImageForImagick($destPath)
    {
        $this->srcImg->setImageCompression(Imagick::COMPRESSION_JPEG);
        $this->srcImg->setImageCompressionQuality($this->qualityJpeg);
        $this->srcImg->cropImage($this->srcW, $this->srcH, $this->srcX, $this->srcY);
        $this->srcImg->resizeImage($this->destW, $this->destH, Imagick::FILTER_LANCZOS, 0.9, false);

        switch ($this->getExtension()) {
            case self::MIME_GIF:
            case self::MIME_PNG:
                $this->srcImg->setImageBackgroundColor(new ImagickPixel('transparent'));
                break;
            default:
                $this->srcImg->setImageBackgroundColor(new ImagickPixel("rgb($this->colorR, $this->colorG, $this->colorB)"));
        }
        if ($this->destW === $this->canvasW) {
            // 横幅いっぱい
            $this->srcImg->spliceImage(0, $this->destY, 0, 0);
            $this->srcImg->spliceImage(0, $this->destY, 0, $this->destY + $this->destH);
        } else {
            // 縦幅いっぱい
            $this->srcImg->spliceImage($this->destX, 0, 0, 0);
            $this->srcImg->spliceImage($this->destX, 0, $this->destX + $this->destW, 0);
        }
        $this->srcImg->stripImage();
        $this->srcImg->writeImages($destPath, true);
        $this->srcImg->clear();
        $this->srcImg->destroy();
    }

    private function resizeToAspectFit()
    {
        $this->srcW = $this->originalW;
        $this->srcH = $this->originalH;

        $srcRatio = $this->originalW / $this->originalH;
        $destRatio = $this->canvasW / $this->canvasH;

        if ($srcRatio > $destRatio) {
            // 横幅いっぱい
            $this->destW = $this->canvasW;
            $this->destH = ceil($this->destW / $srcRatio);
            $this->destY = ceil(($this->canvasH - $this->destH) / 2);
        } else {
            // 縦幅いっぱい
            $this->destH = $this->canvasH;
            $this->destW = ceil($this->destH * $srcRatio);
            $this->destX = ceil(($this->canvasW - $this->destW) / 2);
        }
    }

    private function resieToAspectFill()
    {
        $this->srcW = $this->originalW;
        $this->srcH = $this->originalH;

        $this->destW = $this->canvasW;
        $this->destH = $this->canvasH;

        $srcRatio = $this->originalW / $this->originalH;
        $destRatio = $this->canvasW / $this->canvasH;


        if ($srcRatio > $destRatio) {
            // 左右をトリミング
            $this->srcH = $this->originalH;
            $this->srcW = ceil($this->srcH * $destRatio);
            $this->srcX = ceil(($this->originalW - $this->srcW) / 2);
        } else {
            // 上下をトリミング
            $this->srcH = ceil($this->srcW / $destRatio);
            $this->srcW = $this->originalW;
            $this->srcY = ceil(($this->originalH - $this->srcH) / 2);
        }
    }

    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    public function setQuality($quality)
    {
        $this->qualityJpeg = $quality;

        return $this;
    }

    public function setBgColor($color)
    {
        $color = ltrim($color, '#');

        if (preg_match('/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/', $color, $matches)) {
            $this->colorR = hexdec($matches[1]);
            $this->colorG = hexdec($matches[2]);
            $this->colorB = hexdec($matches[3]);
        }
        if (
            0
            || $this->colorR > 255 || $this->colorR < 0
            || $this->colorG > 255 || $this->colorG < 0
            || $this->colorB > 255 || $this->colorB < 0
        ) {
            AcmsLogger::warning('画像リサイズで、無効な色指定されました', [
                'colorR' => $this->colorR,
                'colorG' => $this->colorG,
                'colorB' => $this->colorB,
            ]);
            throw new Exception('Incorrect Color Value');
        }

        return $this;
    }

    public function save($path)
    {
        if ($this->engine === 'imagick') {
            $this->createDestImageForImagick($path);
            Image::createWebpWithImagick($path, $path . '.webp');
        } else {
            $this->createDestImage();

            switch ($this->getExtension()) {
                case self::MIME_GIF:
                    imagegif($this->destImg, $path);
                    break;
                case self::MIME_PNG:
                    imagepng($this->destImg, $path);
                    Image::createWebpWithGd($this->destImg, $path . '.webp', $this->qualityJpeg);
                    break;
                case self::MIME_BMP:
                    imagewbmp($this->destImg, $path);
                    break;
                case self::MIME_XBM:
                    imagexbm($this->destImg, $path);
                    break;
                default:
                    imagejpeg($this->destImg, $path, $this->qualityJpeg);
                    Image::createWebpWithGd($this->destImg, $path . '.webp', $this->qualityJpeg);
                    break;
            }
        }
        Image::optimize($path);
        Storage::changeMod($path);

        return $this;
    }

    public function resize($width, $height)
    {
        $this->canvasW = $width;
        $this->canvasH = $height;

        switch ($this->mode) {
            case self::SCALE_TO_FILL:
                $this->srcW = $this->originalW;
                $this->srcH = $this->originalH;
                $this->destW = $width;
                $this->destH = $height;
                break;
            case self::SCALE_ASPECT_FIT:
                $this->resizeToAspectFit();
                break;
            case self::SCALE_ASPECT_FILL:
                $this->resieToAspectFill();
                break;
        }

        return $this;
    }

    public function resizeToHeight($height)
    {
        $this->canvasH = $height;
        $this->srcW = $this->originalW;
        $this->srcH = $this->originalH;
        $ratio = $height / $this->originalH;

        if ($height < $this->originalH) {
            $this->destH = $height;
            $this->destW = ceil($this->originalW * $ratio);
            $this->canvasW = $this->destW;
        } else {
            $this->destW = $this->originalW;
            $this->destH = $this->originalH;
            $this->canvasW = $this->originalW;
            $this->destY = ceil(($height - $this->originalH) / 2);
        }

        return $this;
    }

    public function resizeToWidth($width)
    {
        $this->canvasW = $width;
        $this->srcW = $this->originalW;
        $this->srcH = $this->originalH;
        $ratio = $width / $this->originalW;

        if ($width < $this->originalW) {
            $this->destW = $width;
            $this->destH = ceil($this->originalH * $ratio);
            $this->canvasH = $this->destH;
        } else {
            $this->destW = $this->originalW;
            $this->destH = $this->originalH;
            $this->canvasH = $this->originalH;
            $this->destX = ceil(($width - $this->originalW) / 2);
        }

        return $this;
    }
}
