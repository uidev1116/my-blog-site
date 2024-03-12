<?php

namespace Acms\Services\Update\System;

use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Common;
use Acms\Services\Facades\Logger;

class CheckForUpdate
{
    /**
     * メジャーバージョン
     */
    public const MAJOR_VERSION = 1;

    /**
     * マイナーバージョン
     */
    public const MINOR_VERSION = 2;

    /**
     * パッチバージョン
     */
    public const PATCH_VERSION = 3;

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $cache_path;

    /**
     * @var string
     */
    protected $schema_path;

    /**
     * @var string
     */
    protected $jsonString;
    /**
     * @var \stdClass
     */
    protected $data;

    /**
     * @var string
     */
    protected $updateVersion;

    /**
     * @var string
     */
    protected $downGradeVersion;

    /**
     * @var string
     */
    protected $changelogUrl;

    /**
     * @var array
     */
    protected $changelogArray;

    /**
     * @var string
     */
    protected $packageUrl;

    /**
     * @var string
     */
    protected $downGradePackageUrl;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var int
     */
    protected $finalCheckTime;

    /**
     * @var array
     */
    protected $releaseNote;

    /**
     * ValidateVersion constructor.
     *
     * @param string $endpoint
     * @param string $schema_path
     */
    public function __construct($endpoint, $cache_path, $schema_path)
    {
        $this->endpoint = $endpoint;
        $this->schema_path = $schema_path;
        $this->cache_path = $cache_path;

        try {
            $this->finalCheckTime = Storage::lastModified($this->cache_path);
        } catch (\Exception $e) {
        }
    }

    /**
     * Getter: アップデートバージョン
     *
     * @return string
     */
    public function getUpdateVersion()
    {
        return $this->updateVersion;
    }

    /**
     * Getter: アップデートバージョン
     *
     * @return string
     */
    public function getDownGradeVersion()
    {
        return $this->downGradeVersion;
    }

    /**
     * Getter: アップグレードパッケージのダウンロードURL
     *
     * @return string
     */
    public function getPackageUrl()
    {
        return $this->packageUrl;
    }

    /**
     * Getter: アップグレードパッケージのダウンロードURL
     *
     * @return string
     */
    public function getDownGradePackageUrl()
    {
        return $this->downGradePackageUrl;
    }

    /**
     * Getter: アップグレードパッケージの解凍後の本体までのパスのGetter
     *
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * Getter: 最終チェック時間
     *
     * @return int
     */
    public function getFinalCheckTime()
    {
        return $this->finalCheckTime;
    }

    /**
     * Getter: Changelog URL
     *
     * @return string
     */
    public function getChangelogUrl()
    {
        return $this->changelogUrl;
    }

    /**
     * Changelog
     *
     * @return array
     */
    public function getChangelogArray()
    {
        return $this->changelogArray;
    }

    /**
     * Getter: ReleaseNote
     *
     * @return array
     */
    public function getReleaseNote()
    {
        return $this->releaseNote;
    }

    /**
     * バージョンアップが存在するか確認
     *
     * @param string $php_version
     * @param int $type
     * @return bool|self
     */
    public function check($php_version, $type = self::PATCH_VERSION)
    {
        $string = $this->request($this->endpoint);

        if ($this->checkForUpdate($string, $php_version, $type)) {
            $this->finalCheckTime = REQUEST_TIME;
            return true;
        }
        return false;
    }

    /**
     * バージョンアップが存在するか確認（キャッシュ利用）
     *
     * @param string $php_version
     * @param int $type
     * @return bool
     */
    public function checkUseCache($php_version, $type = self::PATCH_VERSION)
    {
        try {
            $string = Storage::get($this->cache_path);
            if (empty($string)) {
                throw new \RuntimeException('empty');
            }
        } catch (\Exception $e) {
            return false;
        }
        if ($this->checkForUpdate($string, $php_version, $type)) {
            return true;
        }
        return false;
    }

    /**
     * ダウングレードバージョンが存在するか確認（キャッシュ利用）
     *
     * @param string $php_version
     * @return bool
     */
    public function checkDownGradeUseCache($php_version)
    {
        try {
            $string = Storage::get($this->cache_path);
            if (empty($string)) {
                throw new \RuntimeException('empty');
            }
        } catch (\Exception $e) {
            return false;
        }
        if ($this->checkForDownGrade($string, $php_version)) {
            return true;
        }
        return false;
    }

    /**
     * 実際のチェックバージョン処理
     *
     * @param string $string
     * @param string $php_version
     * @param int $type
     * @return bool|self
     */
    protected function checkForUpdate($string, $php_version, $type = self::PATCH_VERSION)
    {
        try {
            $php_version = strtolower($php_version);
            $this->decode($string);

            $update_version = $this->checkAcmsVersion($type);
            if (empty($update_version)) {
                return false;
            }
            $this->releaseNote = $this->createReleaseNote($update_version->version);
            $this->updateVersion = $update_version->version;
            $this->changelogUrl = $update_version->changelog->link;
            $this->changelogArray = $update_version->changelog->logs;
            $package = $this->checkPhpVersion($update_version->packages, $php_version);
            if (empty($package)) {
                return false;
            }
            $this->packageUrl = $package->download;
            $this->rootDir = $package->root_dir;

            return true;
        } catch (\Exception $e) {
            Logger::notice($e->getMessage(), Common::exceptionArray($e));
        }
        return false;
    }

    /**
     * 実際のダウングレードバージョン処理
     *
     * @param string $string
     * @param string $php_version
     * @return bool|self
     */
    protected function checkForDownGrade($string, $php_version)
    {
        try {
            $php_version = strtolower($php_version);
            $this->decode($string);

            $down_grade_version = $this->checkAcmsDownGradeVersion();
            if (empty($down_grade_version)) {
                return false;
            }
            $this->downGradeVersion = $down_grade_version->version;
            $package = $this->checkPhpVersion($down_grade_version->packages, $php_version);
            if (empty($package)) {
                return false;
            }
            $this->downGradePackageUrl = $package->download;
            $this->rootDir = $package->root_dir;

            return true;
        } catch (\Exception $e) {
            Logger::notice($e->getMessage(), Common::exceptionArray($e));
        }
        return false;
    }

    /**
     * phpのバージョンチェック
     *
     * @param object $packages
     * @param string $php_version
     * @return bool|object
     */
    protected function checkPhpVersion($packages, $php_version)
    {
        foreach ($packages as $package) {
            $php_min_version = $package->php_min_version;
            $php_max_version = str_replace('x', '99999', $package->php_max_version);
            if (
                1
                && version_compare($php_version, $php_min_version, '>=')
                && version_compare($php_version, $php_max_version, '<=')
            ) {
                return $package;
            }
        }
        return false;
    }

    /**
     * a-blog cmsのバージョンチェック
     *
     * @param 1|2|3 $type
     * @return bool|object
     */
    protected function checkAcmsVersion($type = self::PATCH_VERSION)
    {
        $current = strtolower(VERSION);
        switch ($type) {
            case self::PATCH_VERSION:
                $method = 'isPatchVersion';
                break;
            case self::MINOR_VERSION:
                $method = 'isMinorVersion';
                break;
            case self::MAJOR_VERSION:
                $method = 'isMajorVersion';
                break;
        }
        foreach ($this->data->versions as $item) {
            $version = $item->version;
            if (call_user_func(array($this, $method), $version, $current)) {
                return $item;
            }
        }
        return false;
    }

    /**
     * a-blog cmsのダウングレードバージョンチェック
     *
     * @return bool|object
     */
    protected function checkAcmsDownGradeVersion()
    {
        foreach ($this->data->versions as $item) {
            if ($this->isDownGradeVersion($item->version)) {
                return $item;
            }
        }
        return false;
    }

    /**
     * バージョンに合わせたリリースノート作成
     *
     * @param string $updateCmsVersion
     * @return array
     */
    protected function createReleaseNote($updateCmsVersion)
    {
        if (!property_exists($this->data, 'releaseNote')) {
            return array();
        }
        $allNote = $this->data->releaseNote;
        if (empty($allNote)) {
            return array();
        }
        $partOfNote = array();
        foreach ($allNote as $note) {
            if (
                1
                && version_compare($note->version, strtolower(VERSION), '>')
                && version_compare($note->version, $updateCmsVersion, '<=')
            ) {
                $partOfNote[] = $note;
            }
        }
        return $partOfNote;
    }

    /**
     * ダウングレードバージョンがあるかチェック
     *
     * @param $version
     * @return bool
     */
    protected function isDownGradeVersion($version)
    {
        $versionAry = preg_split('/[-+\.\_]/', $version);
        $licenseMajorVersion = intval(substr(LICENSE_SYSTEM_MAJOR_VERSION, 0, 1));
        $licenseMinorVersion = intval(substr(LICENSE_SYSTEM_MAJOR_VERSION, 1));

        $current = "$licenseMajorVersion.$licenseMinorVersion.0";
        $next = $licenseMinorVersion + 1;
        $next = "$licenseMajorVersion.$next.0";
        if (
            1
            && version_compare($version, $current, '>')
            && version_compare($version, $next, '<')
            && intval($versionAry[1]) === $licenseMinorVersion
        ) {
            return true;
        }
        return false;
    }

    /**
     * パッチバージョンがあるか判定
     *
     * @param string $version
     * @param string $current
     * @return bool|object
     */
    protected function isPatchVersion($version, $current)
    {
        $versionAry = preg_split('/[-+\.\_]/', $version);
        $currentAry = preg_split('/[-+\.\_]/', $current);
        $next = (intval($currentAry[1]) + 1);
        $next = "{$currentAry[0]}.{$next}.0";
        if (
            1
            && version_compare($version, $current, '>')
            && version_compare($version, $next, '<')
            && $versionAry[1] === $currentAry[1]
        ) {
            return true;
        }
        return false;
    }

    /**
     * マイナーバージョンがあるか判定
     *
     * @param string $version
     * @param string $current
     * @return bool|object
     */
    protected function isMinorVersion($version, $current)
    {
        $versionAry = preg_split('/[-+\.\_]/', $version);
        $currentAry = preg_split('/[-+\.\_]/', $current);
        $next = (intval($currentAry[0]) + 1);
        $next = "{$next}.0.0";
        if (
            1
            && version_compare($version, $current, '>')
            && version_compare($version, $next, '<')
            && $versionAry[0] === $currentAry[0]
        ) {
            return true;
        }
        return false;
    }

    /**
     * メジャーバージョンがあるか判定
     *
     * @param string $version
     * @param string $current
     * @return bool|object
     */
    protected function isMajorVersion($version, $current)
    {
        $tmp = preg_split('/[-+\.\_]/', $current);
        $next = ++$tmp[0];
        $next = "{$next}.0.0";
        if (
            1
            && version_compare($version, $current, '>')
            && version_compare($version, $next, '>=')
        ) {
            return true;
        }
        return false;
    }

    /**
     * JSONをバリデート & デコード
     *
     * @param $string
     */
    protected function decode($string)
    {
        $data = json_decode($string);
        if (!property_exists($data, 'versions') || !property_exists($data, 'releaseNote')) {
            throw new \RuntimeException('取得したアップデートバージョンが記載されたJSONが不正な形式です。');
        }
        $this->data = $data;
    }

    /**
     * Request
     *
     * @param string $endpoint
     * @return mixed
     */
    protected function request($endpoint)
    {
        if (!defined('CURL_SSLVERSION_TLSv1_2')) {
            define('CURL_SSLVERSION_TLSv1_2', 6); // phpcs:ignore
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);

        $string = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (empty($string) || $status !== 200) {
            throw new \RuntimeException($status . ' : Failed to get the json');
        }
        if ($charset = mb_detect_encoding($string, 'UTF-8, EUC-JP, SJIS') and 'UTF-8' <> $charset) {
            $string = mb_convert_encoding($string, 'UTF-8', $charset);
        }
        $this->jsonString = $string;

        Storage::put($this->cache_path, $string);

        return $string;
    }
}
