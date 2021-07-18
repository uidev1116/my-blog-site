<?php

namespace Acms\Services\Storage\Contracts;

use Config;

abstract class Base
{
    /**
     * @var int
     */
    protected $fileMod = 438; // ( 0666 = 438 | 0644 = 420 | 0604 = 388)

    /**
     * @var int $directoryMod
     */
    protected $directoryMod = 511; // ( 0777 = 511 | 0755 = 493 | 0705 = 453)

    /**
     * Base constructor
     */
    public function __construct()
    {

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
        if ( strlen($suffix) ) {
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
        if ( strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ) {
            if ( $charset = mb_detect_encoding($path, 'UTF-8, EUC-JP, SJIS-win, SJIS') ) {
                return mb_convert_encoding($path, "CP932", $charset);
            }
        }
        return $path;
    }
}