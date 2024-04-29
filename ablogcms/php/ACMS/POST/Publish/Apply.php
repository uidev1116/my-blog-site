<?php

class ACMS_POST_Publish_Apply extends ACMS_POST_Publish
{
    public $pointer = null;

    function post()
    {
        if (!IS_LICENSED) {
            return false;
        }

        if (roleAvailableUser()) {
            if (!roleAuthorization('publish_exec', BID)) {
                return false;
            }
        } else {
            if (!sessionWithCompilation()) {
                return false;
            }
        }

        if (BID != 1) {
            $ParentConfig = loadConfig(ACMS_RAM::blogParent(BID));
            if ('on' != $ParentConfig->get('publish_children_allow')) {
                return false;
            }
        }

        $Config = loadConfig(BID);

        $resources = $Config->getArray('publish_resource_uri');
        $layoutOnly = $Config->getArray('publish_layout_only');
        $tgtTheme = $Config->getArray('publish_target_theme');
        $tgtPath = $Config->getArray('publish_target_path');

        $resourceCnt = count($resources);
        $layoutOnlyCnt = count($layoutOnly);
        $tgtThemeCnt = count($tgtTheme);
        $tgtPathCnt = count($tgtPath);

        $max = min($resourceCnt, $layoutOnlyCnt, $tgtThemeCnt, $tgtPathCnt);

        $basePath = SCRIPT_DIR . THEMES_DIR;

        $successLog = [];
        $errorLog = [];
        for ($i = 0; $i < $max; $i++) {
            $uri = $resources[$i];
            $layout = $layoutOnly[$i];
            $theme = $tgtTheme[$i];
            $path = $tgtPath[$i];
            $this->pointer = md5($uri . $theme . $path);

            if (!preg_match('@^/@', $path)) {
                $path = '/' . $path;
            }
            if (!$this->validateUri($uri)) {
                $errorLog[] = [
                    'url' => $uri,
                    'path' => $path,
                    'message' => 'URLが不正です',
                ];
                continue;
            }
            if (!$this->isWritable($basePath . $theme)) {
                $errorLog[] = [
                    'url' => $uri,
                    'path' => $basePath . $theme,
                    'message' => '書き込み権限がありません',
                ];
                continue;
            }
            $fullpath = $basePath . $theme . $path;
            if (!$this->isExists($fullpath)) {
                $errorLog[] = [
                    'url' => $uri,
                    'path' => $fullpath,
                    'message' => '書き込み権限がありません',
                ];
                continue;
            }
            try {
                $req = Http::init($uri, 'GET');
                $ua = 'publish_ablogcms/' . VERSION;
                if ($layout === 'layout') {
                    $headers['User-Agent'] = ONLY_BUILD_LAYOUT;
                }
                $req->setRequestHeaders([
                    'Accept-Language: ' . HTTP_ACCEPT_LANGUAGE,
                    'User-Agent ' . $ua,
                ]);
                $response = $req->send();
                if (strpos(Http::getResponseHeader('http_code'), '200') === false) {
                    throw new \RuntimeException(Http::getResponseHeader('http_code'));
                }
                $body = $response->getResponseBody();

                if (!!($fp = fopen($fullpath, 'w'))) {
                    fwrite($fp, $body);
                    fclose($fp);

                    $successLog[] = [
                        'url' => $uri,
                        'path' => $fullpath,
                    ];
                } else {
                    $this->addError('failed to put content in ' . $fullpath);
                    $errorLog[] = [
                        'url' => $uri,
                        'path' => $fullpath,
                    ];
                }
            } catch (Exception $e) {
                $this->addError($e->getMessage());
                $errorLog[] = [
                    'url' => $uri,
                    'path' => $fullpath,
                    'message' => $e->getMessage(),
                ];
            }
        }
        if (empty($errorLog) && count($successLog) > 0) {
            $this->addMessage(gettext('書き出しに成功しました'));
            AcmsLogger::info('テンプレートの書き出しに成功しました', $successLog);
        } else {
            AcmsLogger::warning('テンプレート書き出しに失敗しました', $errorLog);
        }
        return $this->Post;
    }

    function validateUri(&$uri)
    {
        $uri = setGlobalVars($uri);
        if (preg_match('@^(https|http|acms)://@', $uri, $match)) {
            if ('acms' == $match[1]) {
                $Q = parseAcmsPath(preg_replace('@^acms://@', '', $uri));
                $uri = acmsLink($Q, false);
            }
            return true;
        } else {
            $this->addError('invalid url in ' . $uri);
            return false;
        }
    }

    function isWritable($path)
    {
        if (Storage::isWritable($path)) {
            return true;
        } else {
            $this->addError('failed to write in ' . $path);
            return false;
        }
    }

    function isExists($path)
    {
        if (Storage::exists($path)) {
            if ($this->isWritable($path)) {
                return true;
            } else {
                $this->addError('no such file in ' . $path);
                return false;
            }
        } else {
            return true;
        }
    }
}
