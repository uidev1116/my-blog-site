<?php

namespace Acms\Services\Config;

use Storage;
use DB;
use SQL;
use Field;
use Auth;
use Cache;
use Config;
use ACMS_RAM;
use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Helper
{
    /**
     * @var \Field
     */
    protected $config;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->config = &Field::singleton('config');
        $this->cache = Cache::config();
    }

    /**
     * ブログID, ルールID, モジュールIDによって指定されたコンテキストのコンフィグをFieldで返す
     *
     * @param int $bid
     * @param int $rid
     * @param int $mid
     * @param int $setid
     *
     * @return \Field
     */
    public function load($bid = null, $rid = null, $mid = null, $setid = null)
    {
        $cacheKey = "cache-config-$bid-$rid-$mid-$setid";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        if ($mid) {
            $setid = null;
        }
        $DB = DB::singleton(dsn());
        $config = new Field();

        $SQL = SQL::newSelect('config');
        $SQL->addSelect('config_key');
        $SQL->addSelect('config_value');
        $SQL->addWhereOpr('config_rule_id', $rid);
        $SQL->addWhereOpr('config_module_id', $mid);
        $SQL->addWhereOpr('config_set_id', $setid);
        if (!empty($bid)) $SQL->addWhereOpr('config_blog_id', $bid);
        $SQL->setOrder('config_sort');
        $q = $SQL->get(dsn());

        $all = $DB->query($q, 'all');
        foreach ($all as $row) {
            $config->addField($row['config_key'], $row['config_value']);
        }
        $this->cache->put($cacheKey, $config, 0, $this->getCacheTags($bid, $rid, $mid, $setid));

        return $config;
    }

    /**
     * コンフィグの保存
     *
     * @param Field $Config
     * @param int $bid
     * @param int $rid
     * @param int $mid
     * @param int $setid
     *
     * @return bool
     */
    public function saveConfig($Config, $bid = BID, $rid = null, $mid = null, $setid = null)
    {
        if ($mid) {
            $setid = null;
        }
        $this->resetConfig($Config, $bid, $rid, $mid, $setid);

        $DB = DB::singleton(dsn());
        $fds = $Config->listFields();
        $sort = 1;

        foreach ($fds as $fd) {
            $vals = $Config->getArray($fd, true);
            if (!count($vals)) $vals[0] = null;

            foreach ($vals as $val) {
                $SQL = SQL::newInsert('config');
                $SQL->addInsert('config_key', $fd);
                $SQL->addInsert('config_value', strval($val));
                $SQL->addInsert('config_sort', $sort++);
                $SQL->addInsert('config_rule_id', $rid);
                $SQL->addInsert('config_module_id', $mid);
                $SQL->addInsert('config_set_id', $setid);
                $SQL->addInsert('config_blog_id', $bid);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }
        return true;
    }

    /**
     * コンフィグのリセット
     *
     * @param Field $Config
     * @param int $bid
     * @param int $rid
     * @param int $mid
     * @param int $setid
     *
     * @return void
     */
    public function resetConfig($Config, $bid = BID, $rid = null, $mid = null, $setid = null)
    {
        $DB = DB::singleton(dsn());
        $fds = $Config->listFields();

        foreach ($fds as $fd) {
            $SQL = SQL::newDelete('config');
            if (preg_match('/^banner-(.*)@(.*)$/', $fd, $match)) {
                $fd = 'banner-%@' . $match[2];
                $SQL->addWhere('config_key LIKE \'' . $fd . '\'');
            } else {
                $SQL->addWhereOpr('config_key', $fd);
            }
            $SQL->addWhereOpr('config_rule_id', $rid);
            $SQL->addWhereOpr('config_module_id', $mid);
            $SQL->addWhereOpr('config_set_id', $setid);
            $SQL->addWhereOpr('config_blog_id', $bid);
            $DB->query($SQL->get(dsn()), 'exec');
        }
        $this->forgetCache($bid, $rid, $mid, $setid);
    }

    /**
     * キャッシュクリア
     *
     * @param int $bid
     * @param int $rid
     * @param int $mid
     * @param int $setid
     */
    public function forgetCache($bid = null, $rid = null, $mid = null, $setid = null)
    {
        $this->cache->invalidateTags($this->getCacheTags($bid, $rid, $mid, $setid));
    }

    /**
     * キャッシュの全クリア
     */
    public function cacheClear()
    {
        $this->cache->flush();
    }

    /**
     * config.sytem.yamlに記録されているデフォルトのコンフィグを連想配列で返す
     *
     * @return array
     */
    public function loadDefault()
    {
        $cacheKey = 'cache-default-config';
        if ($this->cache->has($cacheKey) && !$this->needToLoadDefaultConfig()) {
            return $this->cache->get($cacheKey);
        }
        if (!($config = $this->yamlLoad(CONFIG_DEFAULT_FILE))) die('config is broken');
        if ($configUser = $this->yamlLoad(CONFIG_FILE)) {
            $config = array_merge($config, $configUser);
        }
        $this->cache->put($cacheKey, $config);
        return $config;
    }

    /**
     * config.system.yamlに記載されているデフォルトのコンフィグをキャッシュされたFieldで返す
     *
     * @return Field
     */
    public function loadDefaultField()
    {
        $cacheKey = 'cache-default-config-field';

        if ($this->cache->has($cacheKey) && !$this->needToLoadDefaultConfig()) {
            return $this->cache->get($cacheKey);
        }
        $config = new Field();
        foreach ($this->loadDefault() as $key => $val) {
            $config->setField($key, $val);
        }
        $this->cache->put($cacheKey, $config);
        return $config;
    }

    /**
     * 指定されたidに該当するブログのコンフィグをキャッシュされたFieldで返す
     */
    public function loadBlogField($bid)
    {
        return $this->loadBlogConfig($bid);
    }

    /**
     * 指定されたidに該当するコンフィグセットのコンフィグをキャッシュされたFieldで返す
     */
    public function loadConfigSetField($id)
    {
        return $this->loadConfigSet($id);
    }

    /**
     * 指定されたidに該当するブログのコンフィグをFieldで返す
     *
     * @param int $bid
     *
     * @return \Field
     */
    public function loadBlogConfig($bid)
    {
        return $this->load($bid);
    }

    /**
     * 指定されたidに該当するブログのコンフィグセットを考慮したFieldを返す
     */
    public function loadBlogConfigSet($bid)
    {
        $configSetId = ACMS_RAM::blogConfigSetId($bid);
        if ($configSetId) {
            return $this->loadConfigSetField($configSetId);
        }
        return $this->loadBlogField($bid);
    }

    /**
     * 指定されたidに該当するカテゴリーのコンフィグセットを考慮したFieldを返す
     */
    public function loadCategoryConfigSet($cid)
    {
        if (empty($cid)) {
            return false;
        }
        $configSetId = ACMS_RAM::categoryConfigSetId($cid);
        if (empty($configSetId)) {
            return false;
        }
        return $this->loadConfigSetField($configSetId);
    }

    /**
     * 現在のコンテキストでのコンフィグセットIDを返します
     */
    public function getCurrentConfigSetId()
    {
        if (CID) {
            if ($categorySetId = ACMS_RAM::categoryConfigSetId(CID)) {
                return $categorySetId;
            }
        }
        return ACMS_RAM::blogConfigSetId(BID);
    }

    /**
     * 現在のコンテキストでのコンフィグセット名を返します
     */
    public function getCurrentConfigSetName()
    {
        $id = $this->getCurrentConfigSetId();
        if (empty($id)) {
            return '';
        }
        $cacheKey = "cache-config-set-name-$id";
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        $sql = SQL::newSelect('config_set');
        $sql->setSelect('config_set_name');
        $sql->addWhereOpr('config_set_id', $id);
        $name = DB::query($sql->get(dsn()), 'one');

        $this->cache->put($cacheKey, $name);
        return $name;
    }

    /**
     * 指定されたidに該当するルールのコンフィグセットを考慮したFieldを返す
     */
    public function loadRuleConfigSet($rid)
    {
        if (empty($rid)) {
            return false;
        }
        $configSetId = $this->getCurrentConfigSetId();
        if (empty($configSetId)) {
            $configSetId = null;
        }
        return $this->loadRuleConfig($rid, $configSetId);
    }

    /**
     * 指定されたidに該当するコンフィグセットのコンフィグをFieldで返す
     *
     * @param int $id
     * @return \Field
     */
    public function loadConfigSet($id)
    {
        return $this->load(null, null, null, $id);
    }

    /**
     * 指定されたidに該当するルールのコンフィグをFieldで返す
     *
     * @param int $rid
     * @param int $setid
     *
     * @return \Field
     */
    public function loadRuleConfig($rid, $setid = null)
    {
        if ($this->get('global_rule_config_load') === 'global') {
            return $this->load(null, $rid);
        }
        $bid = BID;
        if ($setid) {
            $bid = null;
        }
        return $this->load($bid, $rid, null, $setid);
    }

    /**
     * 指定されたidに該当するモジュールIDのコンフィグをFieldで返す
     *
     * @param int $mid
     * @param int $rid
     *
     * @return \Field
     */
    public function loadModuleConfig($mid, $rid = null)
    {
        if (empty($mid)) {
            $Config = $this->loadBlogConfig(BID);
            if (!!$rid) {
                $Config->overload($this->loadRuleConfig($rid));
            }
            return $Config;
        }
        $Config = $this->load(null, null, $mid);
        if (!!$rid) {
            $Config->overload($this->load(null, $rid, $mid));
        }
        return $Config;
    }

    /**
     * ルールのモジュールコンフィグが存在するかチェック
     *
     * @return bool
     */
    public function isExistsRuleModuleConfig()
    {
        $Get = new Field(Field::singleton('get'));

        if (!($rid = intval($Get->get('rid')))) {
            $rid = null;
        }
        if (!($mid = intval($Get->get('mid')))) {
            $mid = null;
        }

        if ($rid > 0 && $mid > 0) {
            $Rconfig = $this->load(null, $rid, $mid);
            $Rconfig = $Rconfig->listFields();
            if (empty($Rconfig)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 現在のコンテキストにおける，指定されたキーのコンフィグを返す
     * モジュール内で使用した場合は，モジュールIDで設定されたコンフィグを返す
     *
     * @param string $key
     * @param mixed $default
     * @param int $i
     *
     * @return mixed
     */
    public function get($key = null, $default = null, $i = 0)
    {
        return $this->config->get($key, $default, $i);
    }

    /**
     * 現在のコンテキストにおける，指定されたキーのコンフィグを配列で返す
     * モジュール内で使用した場合は，モジュールIDで設定されたコンフィグを返す
     *
     * @param string $key
     * @param bool $strict
     *
     * @return array
     */
    public function getArray($key, $strict = false)
    {
        return $this->config->getArray($key, $strict);
    }

    /**
     * 現在のコンテキストにおける，指定されたキーのコンフィグを一時的に書き換える
     *
     * @param string $key
     * @param mixed $val
     *
     * @return bool
     */
    public function set($key, $val = null)
    {
        $this->config->setField($key, $val);
        return true;
    }

    /**
     * コンフィグへのアクセス権限チェック
     *
     * @param Field $Config
     * @param int $rid
     * @param int $mid
     * @param int $setid
     *
     * @return Field
     */
    public function setValide($Config, $rid = null, $mid = null, $setid = null)
    {
        $action = 'config_edit';
        $key = null;
        $id = null;

        // action
        if ($mid) {
            $action = 'module_edit';
        } else if (ADMIN === 'publish_index') {
            $action = 'publish_edit';
        }

        // id
        if ($mid && $rid) {
            $key = 'rid_' . $rid;
            $id = 'mid_' . $mid;
        } else if ($mid) {
            $key = 'mid';
            $id = $mid;
        } else if ($setid && $rid) {
            $key = 'setid_' . $setid;
            $id = 'rid_' . $rid;
        } else if ($setid) {
            $key = 'setid';
            $id = $setid;
        } else if ($rid) {
            $key = 'rid';
            $id = $rid;
        }

        // check
        if (roleAvailableUser()) {
            // ロールで権限管理
            $Config->setMethod(
                'config',
                'operative',
                roleAuthorization($action, BID) ?
                    true : Auth::checkShortcut('Config', ADMIN, $key, $id)
            );
        } else {
            // 通常権限
            $Config->setMethod(
                'config',
                'operative',
                sessionWithAdministration() ?
                    true : Auth::checkShortcut('Config', ADMIN, $key, $id)
            );
        }

        return $Config;
    }

    /**
     * タイプ指定によるデータベーススキーマの取得
     *
     * @param string $type
     * @return array|mixed
     */
    public function getDataBaseSchemaInfo($type)
    {
        $defaultYaml = Storage::get(SCRIPT_DIR . PHP_DIR . "config/schema/db.${type}.yaml");
        $config = $this->yamlParse(str_replace('%{PREFIX}', DB_PREFIX, $defaultYaml));

        if (Storage::exists(SCRIPT_DIR . "extension/schema/db.${type}.yaml")) {
            if ($extendYaml = Storage::get(SCRIPT_DIR . "extension/schema/db.${type}.yaml")) {
                if ($extendConfig = $this->yamlParse(str_replace('%{PREFIX}', DB_PREFIX, $extendYaml))) {
                    $config = array_merge($config, $extendConfig);
                }
            }
        }
        return $config;
    }

    /**
     * yamlファイルのパース
     *
     * @param string $path
     *
     * @return mixed
     * @throws ParseException
     */
    public function yamlLoad($path)
    {
        $data = null;
        try {
            if (Storage::exists($path)) {
                $data = @$this->yamlParse(Storage::get($path));
            }
        } catch (ParseException $e) {
            throw $e;
        }
        return $data;
    }

    /**
     * データをyamlに変換してファイルに書き出し
     *
     * @param mixed $data
     * @param string $path
     *
     * @return string
     */
    public function yamlDump($data, $path = '')
    {
        try {
            $yaml = Yaml::dump($data, 2, 4);
            if (empty($path)) {
                return $yaml;
            } else {
                Storage::put($path, $yaml);
            }
        } catch (DumpException $e) {
            throw $e;
        }
        return '';
    }

    /**
     * yamlデータのパース
     *
     * @param string $yaml
     *
     * @return mixed
     */
    public function yamlParse($yaml)
    {
        // 古いyaml対策
        if (substr($yaml, 0, 3) === '---') {
            $yaml = preg_replace('/([^-\'\s]+):\s*\'\'\s*-/', "$1: \n  - ", $yaml);
        }
        return Yaml::parse($yaml);
    }

    /**
     * コンフィグ保存の為のデータ修正
     *
     * @param Field $Config
     *
     * @return Field
     */
    public function fix($Config)
    {
        //-----------------
        // image unit size
        if ($criterions = $Config->getArray('column_image_size_criterion')) {
            $sizes = $Config->getArray('column_image_size');
            foreach ($criterions as $i => $criterion) {
                if (empty($criterion) || empty($sizes[$i])) continue;
                $sizes[$i] = $criterion . $sizes[$i];
            }
            $Config->set('column_image_size', $sizes);
        }
        if ($large_criterion = $Config->get('image_size_large_criterion')) {
            $Config->set('image_size_large', $large_criterion . $Config->get('image_size_large'));
        }
        if ($tiny_criterion = $Config->get('image_size_tiny_criterion')) {
            $Config->set('image_size_tiny', $tiny_criterion . $Config->get('image_size_tiny'));
        }

        //------
        // size
        $this->fixSize($Config, 'column_map_size');
        $this->fixSize($Config, 'column_video_size');

        //------------
        // theme
        if ($theme = $Config->get('theme')) {
            $_Config = &Field::singleton('config');
            $_Config->set('theme', $theme);
        }

        //-------------
        // file upload
        $listNameAry = array(
            'file_extension_document',
            'file_extension_archive',
            'file_extension_movie',
            'file_extension_audio',
        );
        foreach ($listNameAry as $listName) {
            // リストがなければ処理しない
            if (!$Config->isExists($listName . '@list')) {
                continue;
            }

            // リストを拡張子に分解してセット
            if ($list = $Config->get($listName . '@list')) {
                $Config->setField($listName);
                $exts = array_unique(preg_split(REGEXP_SEPARATER, $list, -1, PREG_SPLIT_NO_EMPTY));
                foreach ($exts as $ext) {
                    $Config->addField($listName, $ext);
                }
            } else {
                $Config->addField($listName, '');
            }

            // リストは処分しておく
            $Config->deleteField($listName . '@list');
        }

        //------------
        // navigation
        if ($Config->getArray('navigation@sort', true)) {
            $all = array();
            $Sort = array();
            foreach ($Config->getArray('navigation@sort', true) as $i => $sort) {
                if (!$label = $Config->get('navigation_label', 0, $i)) continue;
                $pid = intval($Config->get('navigation_parent', 0, $i));
                $id = intval($i + 1);
                // 自分自身を親として参照されたときは，親の設定を解除する
                if ($pid === $id) {
                    $pid = 0;
                }

                $Sort[$pid][$id] = $sort;
                $all[$id] = array(
                    'label' => $label,
                    'pid' => $pid,
                    'uri' => $Config->get('navigation_uri', '', $i),
                    'target' => $Config->get('navigation_target-' . $i),
                    'publish' => $Config->get('navigation_publish-' . $i),
                    'attr' => $Config->get('navigation_attr', '', $i),
                    'a_attr' => $Config->get('navigation_a_attr', '', $i),
                );

                $Config->deleteField('navigation_uri-' . $i);
                $Config->deleteField('navigation_target-' . $i);
                $Config->deleteField('navigation_publish-' . $i);
                $Config->deleteField('navigation_attr-' . $i);
                $Config->deleteField('navigation_a_attr-' . $i);
            }

            if (count($all)) {
                $Config->setField('navigation_label');
                $Config->setField('navigation_parent');
                $Config->setField('navigation_uri');
                $Config->setField('navigation_target');
                $Config->setField('navigation_publish');
                $Config->setField('navigation_attr');
                $Config->setField('navigation_a_attr');

                $Parent = array();
                foreach ($Sort as $pid => $ids) {
                    asort($ids);
                    $Parent[$pid] = array_keys($ids);
                }

                $i = 1;
                $map = array(0 => 0);
                $pidStack = array(0);
                while (count($pidStack)) {
                    $pid = array_pop($pidStack);
                    while ($id = array_shift($Parent[$pid])) {
                        $map[$id] = $i;

                        $Config->addField('navigation_label', $all[$id]['label']);
                        $Config->addField('navigation_uri', $all[$id]['uri']);
                        $Config->addField('navigation_target', $all[$id]['target']);
                        $Config->addField('navigation_publish', $all[$id]['publish']);
                        $Config->addField('navigation_attr', $all[$id]['attr']);
                        $Config->addField('navigation_a_attr', $all[$id]['a_attr']);
                        $Config->addField('navigation_parent', isset($all[$pid]) ? $map[$pid] : 0);
                        $i++;

                        if (isset($Parent[$id])) {
                            $pidStack[] = $pid;
                            $pidStack[] = $id;
                            break;
                        }
                    }
                }
            }

            $Config->deleteField('navigation@sort');
        }

        //--------
        // banner
        if ($aryId = $Config->getArray('banner@id')) {
            $Config->setField('banner_status');
            $Config->setField('banner_src');
            $Config->setField('banner_img');
            $Config->setField('banner_url');
            $Config->setField('banner_alt');
            $Config->setField('banner_attr1');
            $Config->setField('banner_attr2');
            $Config->setField('banner_target');
            $Config->setField('banner_datestart');
            $Config->setField('banner_timestart');
            $Config->setField('banner_dateend');
            $Config->setField('banner_timeend');

            $aryBanner = array();
            $arySort = array();
            foreach ($aryId as $id) {
                $sort = $Config->get('banner-' . $id . '@sort');
                $status = $Config->get('banner-' . $id . '@status');
                $target = $Config->get('banner-' . $id . '@target');
                $src = $Config->get('banner-' . $id . '@src');
                $img = $Config->get('banner-' . $id . '@path');
                $url = $Config->get('banner-' . $id . '@url');
                $alt = $Config->get('banner-' . $id . '@alt');
                $attr1 = $Config->get('banner-' . $id . '@attr1');
                $attr2 = $Config->get('banner-' . $id . '@attr2');
                $datestart = $Config->get('banner-' . $id . '@datestart');
                $timestart = $Config->get('banner-' . $id . '@timestart');
                $dateend = $Config->get('banner-' . $id . '@dateend');
                $timeend = $Config->get('banner-' . $id . '@timeend');

                $Config->deleteField('banner-' . $id);
                $Config->deleteField('banner-' . $id . '@sort');
                $Config->deleteField('banner-' . $id . '@status');
                $Config->deleteField('banner-' . $id . '@target');
                $Config->deleteField('banner-' . $id . '@path');
                $Config->deleteField('banner-' . $id . '@url');
                $Config->deleteField('banner-' . $id . '@alt');
                $Config->deleteField('banner-' . $id . '@attr1');
                $Config->deleteField('banner-' . $id . '@attr2');
                $Config->deleteField('banner-' . $id . '@size');
                $Config->deleteField('banner-' . $id . '@edit');
                $Config->deleteField('banner-' . $id . '@x');
                $Config->deleteField('banner-' . $id . '@y');
                $Config->deleteField('banner-' . $id . '@src');

                $Config->deleteField('banner-' . $id . '@datestart');
                $Config->deleteField('banner-' . $id . '@timestart');
                $Config->deleteField('banner-' . $id . '@dateend');
                $Config->deleteField('banner-' . $id . '@timeend');

                if (empty($src) and empty($img)) continue;

                $aryBanner[$id] = array(
                    'banner_status' => $status,
                    'banner_src' => $src,
                    'banner_img' => $img,
                    'banner_url' => $url,
                    'banner_alt' => $alt,
                    'banner_attr1' => $attr1,
                    'banner_attr2' => $attr2,
                    'banner_target' => $target,
                    'banner_datestart' => $datestart,
                    'banner_timestart' => $timestart,
                    'banner_dateend' => $dateend,
                    'banner_timeend' => $timeend,
                );
                $arySort[$id] = $sort;
            }

            $Config->deleteField('banner@id');
            asort($arySort);
            foreach (array_keys($arySort) as $id) {
                foreach ($aryBanner[$id] as $key => $val) {
                    $Config->addField($key, $val);
                }
            }
        }
        return $Config;
    }

    /**
     * デフォルトのコンフィグをYAMLからロードする必要があるか判定
     *
     * @return boolean
     */
    protected function needToLoadDefaultConfig()
    {
        static $need = null;
        if ($need !== null) {
            return $need;
        }
        $cur = getcwd();
        Storage::changeDir(SCRIPT_DIR);
        $cacheFile = CACHE_DIR . 'cache_default_config';
        $expire = date('Y-m-d H:i:s', Storage::lastModified(CONFIG_FILE));
        if (Storage::exists($cacheFile)) {
            $cacheExpire = date('Y-m-d H:i:s', Storage::lastModified($cacheFile) + 10);
        } else {
            $cacheExpire = '1000-01-01 00:00:00';
        }
        if ($expire > $cacheExpire) {
            if (defined('CACHE_DIR')) {
                try {
                    Storage::makeDirectory(CACHE_DIR);
                    Storage::put($cacheFile, 'cache');
                } catch (\Exception $e) {
                }
            }
            $need = true;
            return true;
        }
        Storage::changeDir($cur);

        $need = false;
        return false;
    }

    protected function fixSize(&$Config, $key)
    {
        $map_sizes = $Config->getArray($key);
        if (empty($map_sizes)) {
            return;
        }

        foreach ($map_sizes as $i => $size) {
            $size = preg_replace('/\s/u', '', $size);
            $size = preg_replace('/[^\d]/u', 'x', $size);
            $map_sizes[$i] = $size;
        }
        $Config->set($key, $map_sizes);
    }

    /**
     * キャッシュのためのタグを取得
     *
     * @param int $bid
     * @param int $rid
     * @param int $mid
     * @param int $setid
     */
    protected function getCacheTags($bid, $rid, $mid, $setid)
    {
        $cacheTags = [];
        if (!empty($bid)) $cacheTags[] = "config-bid-$bid";
        if (!empty($rid)) $cacheTags[] = "config-rid-$rid";
        if (!empty($mid)) $cacheTags[] = "config-mid-$mid";
        if (!empty($setid)) $cacheTags[] = "config-setid-$setid";

        return $cacheTags;
    }
}
