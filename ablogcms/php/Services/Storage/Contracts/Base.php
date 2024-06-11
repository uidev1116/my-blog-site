<?php

namespace Acms\Services\Storage\Contracts;

abstract class Base
{
    /**
     * @var int $directoryMod
     */
    protected $directoryMod = 0;

    /**
     * @var int
     */
    protected $fileMod = 0;

    /**
     * Base constructor
     */
    public function __construct()
    {
        $this->directoryMod = (0775 & ~ umask());
        $this->fileMod = (0664 & ~ umask());
    }

    /**
     * @param int $mod
     *
     * @return void
     */
    public function setFileMod($mod)
    {
        $this->fileMod = $mod;
    }

    /**
     * @param int $mod
     *
     * @return void
     */
    public function setDirectoryMod($mod)
    {
        $this->directoryMod = $mod;
    }

    /**
     * @param string $path
     * @param null $suffix
     *
     * @return string
     */
    public function mbBasename($path, $suffix = null)
    {
        $tmp = preg_split('/[\/\\\\]/', $path);
        $res = end($tmp);
        if ($suffix && strlen($suffix)) {
            $suffix = preg_quote($suffix);
            $res = preg_replace("/({$suffix})$/u", "", $res);
        }
        return $res;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function convertStrToLocal($path)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if ($charset = mb_detect_encoding($path, 'UTF-8, EUC-JP, SJIS-win, SJIS')) {
                return mb_convert_encoding($path, "CP932", $charset);
            }
        }
        return $path;
    }
}
