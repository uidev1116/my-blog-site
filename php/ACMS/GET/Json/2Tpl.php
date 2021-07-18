<?php

class ACMS_GET_Json_2Tpl extends ACMS_GET
{
    /**
     * run
     *
     * @return string
     * @throws \Exception
     */
    public function get()
    {
        $uri = setGlobalVars(config('json_2tpl_source'));

        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        try {
            $response = $this->getJsonCache($uri);
            if ( empty($response) ) {
                $response = $this->getContents($uri);
                $this->saveCache($uri, $response);
            }
            $vars = json_decode($response, true);
            if ( is_array($vars) && $this->is_vector($vars) ) {
                $vars = array(
                    'root' => $vars,
                );
            }
            if ( is_array($vars) ) {
                return $Tpl->render($vars);
            }
        } catch ( \Exception $e ) {
            if ( DEBUG_MODE ) {
                throw $e;
            }
            return '';
        }
        return '';
    }

    /**
     * 添え字が0から連続する数値(=配列とみなせる)ときにtrue
     *
     * @param array $ary
     * @return boolean
     */
    protected function is_vector($ary) {
        return array_values($ary) === $ary;
    }

    /**
     * urlからコンテンツの取得
     *
     * @param string $uri
     *
     * @return string
     */
    protected function getContents($uri)
    {
        try {
            $contents = @file_get_contents($uri);
            if (empty($contents)) {
                throw new \RuntimeException('Empty contents.');
            }
        } catch ( \Exception $e ) {
            return '';
        }
        if ( $charset = mb_detect_encoding($contents, 'UTF-8, EUC-JP, SJIS-win, SJIS') and 'UTF-8' <> $charset ) {
            $contents = mb_convert_encoding($contents, 'UTF-8', $charset);
        }
        return $contents;
    }

    /**
     * キャッシュの取得
     *
     * @param string $uri
     *
     * @return string|bool
     */
    protected function getJsonCache($uri)
    {
        $id = $this->getCacheId($uri);

        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('cache');
        $SQL->setSelect('cache_data');
        $SQL->addWhereOpr('cache_id', $id);
        $SQL->addWhereOpr('cache_expire', date('Y-m-d H:i:s', REQUEST_TIME), '>');
        $SQL->addWhereOpr('cache_blog_id', 0);

        if ( $cache = $DB->query($SQL->get(dsn()), 'one') ) {
            return $cache;
        }
        return false;
    }

    /**
     * キャッシュの保存
     *
     * @param string $uri
     * @param string $contents
     */
    protected function saveCache($uri, $contents)
    {
        $id = $this->getCacheId($uri);

        $DB = DB::singleton(dsn());
        $SQL = SQL::newDelete('cache');
        $SQL->addWhereOpr('cache_id', $id, '=', 'OR');
        $SQL->addWhereOpr('cache_expire', date('Y-m-d H:i:s', REQUEST_TIME), '<', 'OR');
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL = SQL::newInsert('cache');
        $SQL->addInsert('cache_id', $id);
        $SQL->addInsert('cache_data', $contents);
        $SQL->addInsert('cache_expire', date('Y-m-d H:i:s', REQUEST_TIME + config('json_2tpl_cache_expire', 0)));
        $SQL->addInsert('cache_blog_id', 0);
        $DB->query($SQL->get(dsn()), 'exec');
    }

    /**
     * キャッシュidの取得
     *
     * @param string $uri
     * @return string
     */
    protected function getCacheId($uri)
    {
        return md5($uri);
    }
}
