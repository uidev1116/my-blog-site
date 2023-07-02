<?php

class ACMS_POST_Publish_Apply extends ACMS_POST_Publish
{
    var $pointer = null;

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
        $error = 0;

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
                continue;
            }
            if (!$this->isWritable($basePath . $theme)) {
                continue;
            }
            if (!$this->isExists($basePath . $theme . $path)) {
                continue;
            }
            try {
                $req = Http::init($uri, 'GET');
                $ua = 'publish_ablogcms/' . VERSION;
                if ($layout === 'layout') {
                    $headers['User-Agent'] = ONLY_BUILD_LAYOUT;
                }
                $req->setRequestHeaders(array(
                    'Accept-Language: ' . HTTP_ACCEPT_LANGUAGE,
                    'User-Agent ' . $ua,
                ));
                $response = $req->send();
                $body = $response->getResponseBody();

                $fullpath = $basePath . $theme . $path;
                if (!!($fp = fopen($fullpath, 'w'))) {
                    fwrite($fp, $body);
                    fclose($fp);
                } else {
                    $this->addError('failed to put content in ' . $fullpath);
                    $error++;
                }
            } catch (Exception $e) {
                $this->addError($e->getMessage());
                $error++;
            }
        }
        if (empty($error)) {
            $this->addMessage(gettext('書き出しに成功しました'));
        }
        return $this->Post;
    }

    function validateUri(& $uri)
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
