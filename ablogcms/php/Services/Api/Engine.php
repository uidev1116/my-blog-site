<?php

namespace Acms\Services\Api;

use App;
use DB;
use SQL;
use Common;
use AcmsLogger;
use Field;
use Field_Validation;
use ACMS_Filter;
use Acms\Services\Api\Exceptions\NotFoundModuleException;
use Acms\Services\Api\Exceptions\ApiKeyException;
use Acms\Services\Api\Exceptions\ForbiddenException;

class Engine
{
    /**
     * @var string
     */
    protected $apiKey = '';

    /**
     * @var array
     */
    protected $restrictionReferrer = array();

    /**
     * @var array
     */
    protected $restrictionAddress = array();

    /**
     * @var array
     */
    protected $allowOriginDomains = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = config('x_api_key');
        $this->restrictionReferrer = configArray('api_restriction_referer');
        $this->restrictionAddress = configArray('api_restriction_address');
        $this->allowOriginDomains = configArray('api_allow_domain');
    }

    /**
     * @param string $identifier
     */
    public function get($identifier)
    {
        $noCache = true;
        try {
            $moduleName = $this->getModuleName($identifier);

            $this->validateAddress();
            $this->validateReferrer();
            $this->validateApiKey();

            $opt = ' id="' . $identifier . '"';
            $post = Field_Validation::singleton('post');
            $config = clone Field::singleton('config');

            App::bind('view', 'Acms\Services\View\ApiEngine'); // テンプレートエンジンの切り替え
            define('IS_API_BUILD', true);

            $sql = SQL::newSelect('module');
            $sql->addWhereOpr('module_identifier', $identifier);
            $sql->addWhereOpr('module_name', $moduleName);
            $eagerLoadModule[$moduleName][$identifier] = DB::query($sql->get(dsn()), 'row');
            $res = boot($moduleName, '', $opt, $post, $config, $eagerLoadModule);
            $json = $this->isValidJson($res) ? $res : '{}';
            $noCache = false;
        } catch (NotFoundModuleException $e) {
            AcmsLogger::error('API機能: 有効なモジュールIDが存在しません', [
                'identifier' => $identifier,
            ]);
            httpStatusCode('404 Not Found');
            $json = json_encode(array(
                'status' => 404,
                'error' => '404 Not Found Module',
                'message' => 'モジュールIDが存在しません',
                'path' => REQUEST_PATH,
            ));
        } catch (ApiKeyException $e) {
            $this->logging($e, $moduleName, $identifier);
            httpStatusCode('401 Unauthorized');
            $json = json_encode(array(
                'status' => 401,
                'error' => '401 Unauthorized',
                'message' => $e->getMessage(),
                'path' => REQUEST_PATH,
            ));
        } catch (ForbiddenException $e) {
            $this->logging($e, $moduleName, $identifier);
            httpStatusCode('403 Forbidden');
            $json = json_encode(array(
                'status' => 403,
                'error' => '403 Forbidden',
                'message' => $e->getMessage(),
                'path' => REQUEST_PATH,
            ));
        } catch (\Exception $e) {
            $this->logging($e, $moduleName, $identifier);
            httpStatusCode('404 Not Found');
            $json = json_encode(array(
                'status' => 404,
                'error' => '404 Not Found',
                'message' => $e->getMessage(),
                'path' => REQUEST_PATH,
            ));
        }
        $this->response($json, $noCache);
    }

    /**
     *
     * @param \Exception $e
     * @param string $moduleName
     * @param string $identifier
     * @return void
     */
    protected function logging($e, string $moduleName, string $identifier): void
    {
        $data = [
            'moduleName' => $moduleName,
            'identifier' => $identifier,
        ];
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            $data['x-api-key'] = $_SERVER['HTTP_X_API_KEY'];
        }
        AcmsLogger::error('API機能: ' . $e->getMessage(), $data);
    }

    /**
     * Validate API Key
     */
    protected function validateApiKey()
    {
        if (empty($this->apiKey)) {
            throw new ApiKeyException('APIキーが設定されていません。');
        }
        if (!isset($_SERVER['HTTP_X_API_KEY']) || empty($_SERVER['HTTP_X_API_KEY'])) {
            throw new ApiKeyException('X-API-KEY ヘッダーがありません。');
        }
        if ($this->apiKey !== $_SERVER['HTTP_X_API_KEY']) {
            throw new ApiKeyException('APIキーが一致しません。');
        }
    }

    /**
     * Validate Http Referrer
     */
    protected function validateReferrer()
    {
        $referer = preg_replace('/^https?:\/\//', '', REFERER);
        $match = true;
        if (is_array($this->restrictionReferrer) && count($this->restrictionReferrer) > 0) {
            $match = false;
            foreach ($this->restrictionReferrer as $pattern) {
                if (fnmatch($pattern, $referer)) {
                    $match = true;
                    break;
                }
            }
        }
        if (!$match) {
            throw new ForbiddenException("リファラーの制限によりアクセスが拒否されました。");
        }
    }

    /**
     * Validate Remote Address
     */
    protected function validateAddress()
    {
        $match = true;
        if (is_array($this->restrictionAddress) && count($this->restrictionAddress) > 0) {
            $match = false;
            foreach ($this->restrictionAddress as $ipband) {
                if (in_ipband(REMOTE_ADDR, $ipband)) {
                    $match = true;
                    break;
                }
            }
        }
        if (!$match) {
            throw new ForbiddenException("許可されていない接続元からのアクセスです。");
        }
    }

    /**
     * @param string $identifier
     * @return boolean|string
     */
    protected function getModuleName($identifier)
    {
        $sql = SQL::newSelect('module');
        $sql->setSelect('module_name');
        $sql->addLeftJoin('blog', 'blog_id', 'module_blog_id');
        ACMS_Filter::blogTree($sql, BID, 'ancestor-or-self');
        $sql->addWhereOpr('module_identifier', $identifier);
        $sql->addWhereOpr('module_status', 'open');
        $sql->addWhereOpr('module_api_use', 'on');
        $where  = SQL::newWhere();
        $where->addWhereOpr('module_blog_id', BID, '=', 'OR');
        $where->addWhereOpr('module_scope', 'global', '=', 'OR');
        $sql->addWhere($where);
        $q = $sql->get(dsn());
        $module = DB::query($q, 'one');

        if (empty($module)) {
            throw new NotFoundModuleException();
        }
        return $module;
    }

    /**
     * @param string $json
     * @param boolean $noCache
     */
    protected function response($json, $noCache = false)
    {
        header(PROTOCOL . ' ' . httpStatusCode());
        header("Content-Type: application/json");
        $this->addAllowOriginHeader();
        Common::addSecurityHeader();
        Common::clientCacheHeader();

        $responseBody = gzencode($json);
        if (ZIP_USE) {
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
            echo $responseBody;
        } else {
            echo gzdecode($responseBody);
        }
        if (!$noCache) {
            Common::saveCache(CHID, $responseBody, 'application/json');
        }
        die();
    }

    /**
     * Add Allow Origin Header
     */
    protected function addAllowOriginHeader()
    {
        $match = false;
        header('Access-Control-Allow-Methods: GET, OPTIONS, HEAD');
        header('Access-Control-Allow-Headers: *');

        foreach ($this->allowOriginDomains as $allowOriginDomain) {
            $regex = '/^https?:\/\/' . preg_quote($allowOriginDomain, '/') . '/i';
            if (preg_match($regex, REFERER, $m)) {
                header('Access-Control-Allow-Origin: ' . $m[0]);
                $match = true;
                break;
            }
        }
        if (!$match && isset($this->allowOriginDomains[0])) {
            header('Access-Control-Allow-Origin: ' . $this->allowOriginDomains[0]);
        }
        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
            header(PROTOCOL . ' 200 OK');
            header('Access-Control-Max-Age: 3600');
            die();
        }
    }

    protected function isValidJson(string $json)
    {
        return is_null(json_decode($json)) === false;
    }
}
