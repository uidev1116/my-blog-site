<?php

namespace Acms\Services\Cache;

use Acms\Services\Cache\Adapters\Standard;
use Acms\Services\Cache\Adapters\Tag;
use Acms\Services\Cache\Adapters\NoCache;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;

class CacheManager
{
    protected $config;

    protected $cacheDir;

    /**
     * construct
     */
    public function __construct()
    {
        $this->config = include(PHP_DIR . 'config/cache.php');
        $this->cacheDir = CACHE_DIR;
    }

    /**
     * テンプレート用キャッシュ
     */
    public function template()
    {
        return $this->createStandardCache('template', $this->config['type']['template']);
    }

    /**
     * フィールド用キャッシュ
     */
    public function field()
    {
        return $this->createStandardCache('field', $this->config['type']['field']);
    }

    /**
     * 一時的に使えるキャッシュ
     */
    public function temp()
    {
        return $this->createStandardCache('temp', $this->config['type']['temp']);
    }

    /**
     * モジュール用キャッシュ
     */
    public function module()
    {
        return $this->createStandardCache('module', $this->config['type']['module']);
    }

    /**
     * コンフィグ用キャッシュ
     */
    public function config()
    {
        static $cache = null;
        if ($cache) {
            return $cache;
        }
        try {
            $config = $this->config['type']['config'];
            $driver = $this->createTagDriver($config['driver'], $this->getNameSpace($config['namespace']));
            $cache =  new Tag($driver);
        } catch (\Exception $e) {
            $cache = new NoCache;
        }
        return $cache;
    }

    /**
     * ページ用キャッシュ
     */
    public function page()
    {
        static $cache = null;
        if ($cache) {
            return $cache;
        }
        try {
            $config = $this->config['type']['page'];
            $driver = $this->createTagDriver($config['driver'], $this->getNameSpace($config['namespace']));
            $cache =  new Tag($driver);
        } catch (\Exception $e) {
            $cache = new NoCache;
        }
        return $cache;
    }

    /**
     * タイプ別のキャッシュの全クリア
     */
    public function flush($type)
    {
        if (method_exists($this, $type)) {
            $cache = $this->{$type}();
            $cache->flush();
        }
    }

    /**
     * 全てのキャッシュをクリア
     */
    public function allFlush()
    {
        $this->flush('page');
        $this->flush('template');
        $this->flush('config');
        $this->flush('field');
        $this->flush('temp');
        $this->flush('module');
    }

    /**
     * タイプ別の有効期限切れキャッシュを削除
     */
    public function prune($type)
    {
        if (method_exists($this, $type)) {
            $cache = $this->{$type}();
            $cache->prune();
        }
    }

    /**
     * 全ての有効期限切れキャッシュを削除
     */
    public function allPrune()
    {
        $this->prune('page');
        $this->prune('template');
        $this->prune('config');
        $this->prune('field');
        $this->prune('temp');
        $this->prune('module');
    }

    /**
     * 標準キャッシュの生成
     *
     * @param string $name
     * @param array $config
     */
    protected function createStandardCache($name, $config)
    {
        static $cache = [];
        if (isset($cache[$name])) {
            return $cache[$name];
        }
        try {
            $driver = $this->createDriver($config['driver'], $this->getNameSpace($config['namespace']), $config['lifetime']);
            $cache[$name] =  new Standard($driver);
        } catch (\Exception $e) {
            $cache[$name] = new NoCache;
        }
        return $cache[$name];
    }

    /**
     * 標準キャッシュドライバーの作成
     *
     * @param string $drivers
     * @param string $namespace
     * @param int $lifetime
     */
    protected function createDriver($drivers, $namespace, $lifetime = 0)
    {
        $drivers = array_map('trim', explode('|', $drivers));
        $useDriver = null;
        foreach ($drivers as $driver) {
            $method = 'can' . ucwords($driver) . 'Driver';
            if (method_exists($this, $method)) {
                if ($this->{$method}()) {
                    $useDriver = $driver;
                    break;
                }
            }
        }
        if (empty($useDriver)) {
            throw new \RuntimeException('Cache driver not found.');
        }
        $createMethod = 'create' . ucwords($useDriver) . 'Driver';
        if (method_exists($this, $createMethod)) {
            return $this->{$createMethod}($namespace, $lifetime);
        }
        throw new \RuntimeException('Cache driver not found.');
    }

    /**
     * タグ対応キャッシュドライバーの作成
     */
    protected function createTagDriver($drivers, $namespace)
    {
        $drivers = array_map('trim', explode('|', $drivers));
        $useDriver = null;
        foreach ($drivers as $driver) {
            $method = 'can' . ucwords($driver) . 'TagDriver';
            if (method_exists($this, $method)) {
                if ($this->{$method}()) {
                    $useDriver = $driver;
                    break;
                }
            }
        }
        if (empty($useDriver)) {
            throw new \RuntimeException('Cache driver not found.');
        }
        $createMethod = 'create' . ucwords($useDriver) . 'TagDriver';
        if (method_exists($this, $createMethod)) {
            return $this->{$createMethod}($namespace);
        }
        throw new \RuntimeException('Cache driver not found.');
    }

    /**
     * phpキャッシュドライバーが使用可能か
     */
    protected function canPhpDriver()
    {
        return function_exists('opcache_get_status');
    }

    /**
     * phpキャッシュドライバーの作成
     */
    protected function createPhpDriver($namespace, $lifetime)
    {
        return new PhpFilesAdapter(
            $namespace,
            $defaultLifetime = $lifetime,
            $directory = $this->cacheDir
        );
    }

    /**
     * ファイルキャッシュドライバーが使用可能か
     */
    protected function canFileDriver()
    {
        return true;
    }

    /**
     * ファイルキャッシュドライバーの作成
     */
    protected function createFileDriver($namespace, $lifetime)
    {
        return new FilesystemAdapter(
            $namespace,
            $defaultLifetime = $lifetime,
            $directory = $this->cacheDir
        );
    }

    /**
     * メモリーキャッシュドライバーが使用可能か
     */
    protected function canMemoryDriver()
    {
        return true;
    }

    /**
     * メモリーキャッシュドライバーの作成
     */
    protected function createMemoryDriver($namespace, $lifetime)
    {
        return new ArrayAdapter(
            $defaultLifetime = $lifetime,
            $storeSerialized = false,
            $maxLifetime = 100,
            $maxItems = 1000
        );
    }

    /**
     * APCuキャッシュドライバーが使用可能か
     */
    protected function canApcuDriver()
    {
        return function_exists('apcu_enabled') && \apcu_enabled();
    }

    /**
     * APCuキャッシュドライバーを作成
     */
    protected function createApcuDriver($namespace, $lifetime)
    {
        return new ApcuAdapter(
            $namespace,
            $defaultLifetime = $lifetime,
            $version = null
        );
    }

    /**
     * Redisキャッシュドライバーが使用可能か
     */
    protected function canRedisDriver()
    {
        return true;
    }

    /**
     * Redisキャッシュドライバーを作成
     */
    protected function createRedisDriver($namespace, $lifetime)
    {
        $redisInfo = $this->config['drivers']['redis']['connection'];
        $client = $this->createRedisClient($redisInfo['host'], $redisInfo['port'], $redisInfo['password'], $redisInfo['db']);

        return new RedisAdapter(
            $client,
            $namespace,
            $defaultLifetime = $lifetime
        );
    }

    /**
     * ファイルキャッシュドライバーが使用可能か
     */
    protected function canFileTagDriver()
    {
        return true;
    }

    /**
     * ファイルキャッシュドライバーの作成
     */
    protected function createFileTagDriver($namespace)
    {
        return new FilesystemTagAwareAdapter(
            $namespace,
            $defaultLifetime = 0,
            $directory = $this->cacheDir
        );
    }

    /**
     * Redisキャッシュドライバーが使用可能か
     */
    protected function canRedisTagDriver()
    {
        return true;
    }

    /**
     * Redisキャッシュドライバーを作成
     */
    protected function createRedisTagDriver($namespace)
    {
        $redisInfo = $this->config['drivers']['redis']['connection'];
        $client = $this->createRedisClient($redisInfo['host'], $redisInfo['port'], $redisInfo['password'], $redisInfo['db']);

        return new RedisTagAwareAdapter(
            $client,
            $namespace,
            $defaultLifetime = 0
        );
    }

    /**
     * Redisクライアントを作成
     */
    protected function createRedisClient($host, $port, $password = '', $db = '')
    {
        $connection = 'redis://';
        if (!empty($password)) {
            $connection .= "$password/$host";
        } else {
            $connection .= $host;
        }
        $connection .= ":$port";
        if (!empty($db)) {
            $connection .= "/$db";
        }
        return RedisAdapter::createConnection($connection);
    }

    /**
     * 他システムの衝突を避けるため、ネームスペースにドメインからのハッシュを付与
     *
     * @return string
     */
    protected function getNameSpace($namespace)
    {
        return $namespace . '-' . md5(DOMAIN);
    }
}
