<?php

namespace Acms\Services\Storage;

use Acms\Services\Storage\Contracts\Filesystem as FilesystemInterface;
use Acms\Services\Storage\Contracts\Base;
use Alchemy\Zippy\Adapter\ZipExtensionAdapter;
use Cache;

class Filesystem extends Base implements FilesystemInterface
{
    /**
     * @param string $path
     *
     * @return bool
     */
    public function exists($path)
    {
        $path = $this->convertStrToLocal($path);
        return file_exists($path);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isFile($path)
    {
        $path = $this->convertStrToLocal($path);

        return is_file($path);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isDirectory($path)
    {
        $path = $this->convertStrToLocal($path);

        return is_dir($path);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isWritable($path)
    {
        $path = $this->convertStrToLocal($path);

        return is_writable($path);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isReadable($path)
    {
        $path = $this->convertStrToLocal($path);

        return is_readable($path);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isLink($path)
    {
        $path = $this->convertStrToLocal($path);

        return is_link($path);
    }

    /**
     * @param string $path
     * @param int $mode
     *
     * @return bool
     */
    public function changeMod($path, $mode = null)
    {
        $path = $this->convertStrToLocal($path);

        if (is_null($mode)) {
            if ($this->isDirectory($path)) {
                $mode = intval($this->directoryMod);
            } else {
                $mode = intval($this->fileMod);
            }
        }
        if ($this->exists($path)) {
            return chmod($path, $mode);
        }
        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function changeDir($path)
    {
        $path = $this->convertStrToLocal($path);
        if ($this->exists($path)) {
            return chdir($path);
        }
        return false;
    }

    /**
     * @param string $path
     * @param array $info
     *
     * @return array
     */
    public function getImageSize($path, &$info = array())
    {
        $cache = Cache::temp();
        $cacheKey = md5($path);
        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }
        if ($this->exists($path) && $this->isFile($path)) {
            $imageSize = getimagesize($path);
            $cache->put($cacheKey, $imageSize);
            return $imageSize;
        } else if (preg_match('/^https?:\/\//', $path)) {
            $headers = get_headers($path);
            if (isset($headers[0]) && strpos($headers[0], '200 OK') !== false) {
                $imageSize = getimagesize($path);
                $cache->put($cacheKey, $imageSize);
                return $imageSize;
            }
        }
        return false;
    }

    /**
     * @param $path
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function get($path)
    {
        $path = $this->convertStrToLocal($path);

        if ($this->isFile($path)) {
            return @file_get_contents($path);
        }
        throw new \RuntimeException("File does not exist at path {$path}");
    }

    /**
     * @param $path
     *
     * @return bool
     */
    public function remove($path)
    {
        $path = $this->convertStrToLocal($path);

        if ($this->exists($path) && $this->isFile($path)) {
            return @unlink($path);
        }

        return false;
    }

    /**
     * @param string $path
     * @param string $contents
     *
     * @return bool
     */
    public function put($path, $contents)
    {
        $path = $this->convertStrToLocal($path);
        $byte = @file_put_contents($path, $contents);
        if ($byte) {
            $this->changeMod($path);
            return $byte;
        }
        throw new \RuntimeException('failed to put contents in ' . $path);
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function copy($from, $to)
    {
        $to = $this->convertStrToLocal($to);
        $from = $this->convertStrToLocal($from);
        $res = @copy($from, $to);
        $this->changeMod($to);

        if ($this->isFile($from . '.webp')) {
            @copy($from . '.webp', $to . '.webp');
            $this->changeMod($to . '.webp');
        }
        return $res;
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function move($from, $to)
    {
        $to = $this->convertStrToLocal($to);
        $from = $this->convertStrToLocal($from);

        return @rename($from, $to);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public function removeDirectory($dir)
    {
        if (!$this->isDirectory($dir)) {
            return false;
        }

        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($this->isDirectory($file)) {
                $this->removeDirectory($file->getRealPath());
            } else {
                $this->remove($file->getRealPath());
            }
        }
        rmdir($dir);

        return true;
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function copyDirectory($from, $to)
    {
        if (!$this->isDirectory($from)) {
            return false;
        }

        $to = $this->convertStrToLocal($to);
        $from = $this->convertStrToLocal($from);
        $this->makeDirectory($to);
        $dir = opendir($from);
        while (false !== ($file = readdir($dir))) {
            if ($file !== '.' && $file !== '..') {
                if ($this->isDirectory($from . '/' . $file)) {
                    $this->copyDirectory($from . '/' . $file, $to . '/' . $file);
                } else {
                    $this->copy($from . '/' . $file, $to . '/' . $file);
                    $this->changeMod($to . '/' . $file);
                }
            }
        }
        closedir($dir);
        return true;
    }

    /**
     * @param $path
     *
     * @return bool
     */
    public function makeDirectory($path)
    {
        $dir = '';
        foreach (preg_split("@(/)@", $path, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $token) {
            $dir .= $token;
            if (empty($dir)) {
                continue;
            }
            if ('/' === $token) {
                continue;
            }
            if (!$this->isDirectory($dir)) {
                mkdir($this->convertStrToLocal($dir));
                $this->changeMod($dir);
            }
        }
        return $dir;
    }

    /**
     * @param $path
     *
     * @return int Unix time stamp
     */
    public function lastModified($path)
    {
        $path = $this->convertStrToLocal($path);
        if ($this->exists($path)) {
            return filemtime($path);
        }

        return 0;
    }

    /**
     * @return string
     */
    public function archivesDir()
    {
        return sprintf('%03d', BID) . '/' . date('Ym') . '/';
    }

    /**
     * @param string $source
     * @param string $destination
     * @param string $root
     * @param array $exclude
     *
     * @return void
     */
    public function compress($source, $destination, $root = '', $exclude = array())
    {
        $source = $this->convertStrToLocal($source);
        $destination = $this->convertStrToLocal($destination);
        $root = $this->convertStrToLocal($root);
        $zippy = ZipExtensionAdapter::newInstance();

        if (empty($root)) {
            $list = array(basename($destination, '.zip') => $source);
        } else {
            $list = array($root => $source);
        }
        $archive = $zippy->create($destination, $list, true);
        foreach ( $exclude as $path ) {
            $archive->removeMembers($path);
        }
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function unzip($source, $destination)
    {
        $source = $this->convertStrToLocal($source);
        $destination = $this->convertStrToLocal($destination);
        $zippy = ZipExtensionAdapter::newInstance();
        $archive = $zippy->open($source);
        $archive->extract($destination);
    }

    /**
     * @param string $original
     * @param int $num
     *
     * @return string
     */
    public function uniqueFilePath($original, $num = 0)
    {
        if ( $num > 0 ) {
            $info = pathinfo($original);
            $path = $info['dirname'] . "/" . $info['filename'] . "_" . $num;
            if ( isset($info['extension']) ) $path .= "." . $info['extension'];
        } else {
            $path = $original;
        }

        if ( $this->exists($path) ) {
            $num++;
            return $this->uniqueFilePath($original, $num);
        } else {
            return $path;
        }
    }

    /**
     * @param string $source
     *
     * @return string
     */
    public function removeIllegalCharacters($source)
    {
        return preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $source);
    }
}
