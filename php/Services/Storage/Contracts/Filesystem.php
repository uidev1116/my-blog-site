<?php

namespace Acms\Services\Storage\Contracts;

interface Filesystem
{
    /**
     * @param string $path
     *
     * @return bool
     */
    public function exists($path);

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isFile($path);

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isDirectory($path);

    /**
     * @param $path
     *
     * @return bool
     */
    public function isWritable($path);

    /**
     * @param $path
     *
     * @return bool
     */
    public function isReadable($path);

    /**
     * @param $path
     *
     * @return bool
     */
    public function isLink($path);

    /**
     * @param string $path
     * @param int $mode
     *
     * @return bool
     */
    public function changeMod($path, $mode = null);

    /**
     * @param string $path
     * @return bool
     */
    public function changeDir($path);

    /**
     * @param string $path
     * @param array $info
     *
     * @return array
     */
    public function getImageSize($path, &$info = array());

    /**
     * @param $path
     *
     * @return mixed
     */
    public function get($path);

    /**
     * @param $path
     *
     * @return bool
     */
    public function remove($path);

    /**
     * @param string $path
     * @param string $content
     *
     * @return bool
     */
    public function put($path, $content);

    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function copy($from, $to);

    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function move($from, $to);

    /**
     * @param string $dir
     *
     * @return bool
     */
    public function removeDirectory($dir);

    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function copyDirectory($from, $to);

    /**
     * @param $path
     *
     * @return bool
     */
    public function makeDirectory($path);

    /**
     * @param $path
     *
     * @return int Unix time stamp
     */
    public function lastModified($path);

    /**
     * @return string
     */
    public function archivesDir();

    /**
     * @param string $source
     * @param string $destination
     * @param string $root
     * @param array $exclude
     *
     * @return void
     */
    public function compress($source, $destination, $root = '', $exclude = array());

    /**
     * @param string $source
     * @param string $destination
     *
     * @return void
     */
    public function unzip($source, $destination);

    /**
     * @param string $original
     * @param int $num
     *
     * @return string
     */
    public function uniqueFilePath($original, $num = 0);

    /**
     * @param string $source
     *
     * @return string
     */
    public function removeIllegalCharacters($source);
}