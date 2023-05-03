<?php

class ACMS_GET
{
    var $tpl = null;

    var $bid = null;
    var $uid = null;
    var $cid = null;
    var $eid = null;
    var $keyword = null;
    var $tag = null;
    var $tags = array();
    var $field = null;
    var $Field = null;
    var $start = null;
    var $end = null;
    var $alt = null;

    var $step = null;
    var $action = null;

    var $squareSize = null;

    /**
     * @var Field
     */
    var $Q;
    /**
     * @var Field
     */
    var $Get;
    /**
     * @var Field
     */
    var $Post;

    var $_scope = array();
    var $_axis = array(
        'bid' => 'self',
        'cid' => 'self',
    );

    var $mid = null;
    var $mbid = null;
    var $cache = 0;

    function __construct($tpl, $acms, $scope, $axis, $Post, $mid = null, $mbid = null, $identifier = null, $aryMultiAcms = null, $showField = false)
    {
        $this->Post = new Field_Validation($Post, true);
        $this->Get = new Field(Field::singleton('get'));
        $this->Q = new Field(Field::singleton('query'), true);

        $this->tpl = $tpl;
        $this->identifier = $identifier;
        $this->showField = $showField;

        //-----------
        // back link
        if (SUID && EID && in_array(ADMIN, array('entry-edit', 'entry_editor')) && !is_ajax() && (defined('TPL') && !TPL)) {
            $Session =& Field::singleton('session');
            $Session->set('back_link', REQUEST_PATH);
            $Session->set('back_link_eid', EID);
        }

        //-------
        // scope
        $Arg = parseAcmsPath($acms);
        $this->cache = isset($scope['cache']) ? intval($scope['cache']) : 0;

        $this->Q->set('bid', $Arg->get('bid', $this->Q->get('bid')));
        foreach ( array(
                      'cid', 'eid', 'uid', 'keyword', 'tag', 'field', 'start', 'end', 'page', 'order'
                  ) as $key ) {
            $isGlobal = ('global' == (!empty($scope[$key]) ? $scope[$key] : (!empty($this->_scope[$key]) ? $this->_scope[$key] : 'local')));
            if ( 'field' == $key ) {
                $Field = $this->Q->getChild('field');
                if ( !$isGlobal or $Field->isNull() ) {
                    $this->Q->addChild('field', $Arg->getChild('field'));
                }
            } else if ( !$isGlobal or !$this->Q->get($key) ) {
                $val = $Arg->getArray($key);
                if ( ('page' == $key) and (1 > $val[0]) ) $val[0] = 1;
                $this->Q->set($key, array_shift($val));
                foreach ( $val as $argV ) {
                    $this->Q->add($key, $argV);
                }
            } else if ( $isGlobal && (0
                    || ($key == 'start' && $this->Q->get($key) == '1000-01-01 00:00:00')
                    || ($key == 'end' && $this->Q->get($key) == '9999-12-31 23:59:59')
                ) ) {
                $val = $Arg->getArray($key);
                $this->Q->set($key, array_shift($val));
                foreach ( $val as $argV ) {
                    $this->Q->add($key, $argV);
                }
            }
        }

        if ( !$this->Q->isNull('bid') ) {
            $this->bid = intval($this->Q->get('bid'));
        }
        if ( !$this->Q->isNull('cid') ) {
            $this->cid = intval($this->Q->get('cid'));
        }
        if ( !$this->Q->isNull('eid') ) {
            $this->eid = intval($this->Q->get('eid'));
        }
        if ( !$this->Q->isNull('uid') ) {
            $this->uid = intval($this->Q->get('uid'));
        }
        if ( is_array($aryMultiAcms) ) {
            foreach ( $aryMultiAcms as $k => $v ) {
                $isGlobal_ = ('global' == (!empty($scope[$k]) ? $scope[$k] : (!empty($this->_scope[$k]) ? $this->_scope[$k] : 'local')));
                if ( !$isGlobal_ or !$this->Q->get($k) ) $this->{$k} = $v;
            }
        }

        $keyword = $this->Q->get('keyword');
        $qkeyword = $this->Get->get(KEYWORD_SEGMENT);
        if ( 1
            && !empty($qkeyword)
            && config('query_keyword') == 'on'
            && ('global' == (!empty($scope['keyword']) ? $scope['keyword'] : (!empty($this->_scope['keyword']) ? $this->_scope['keyword'] : 'local')))
        ) {
            $keyword = $this->Get->get(KEYWORD_SEGMENT);
        }

        $this->keyword = $keyword;
        $this->start = $this->Q->get('start');
        $this->end = $this->Q->get('end');
        $this->page = $this->Q->get('page');
        $this->order = $this->Q->get('order');

        $this->tag = join('/', $this->Q->getArray('tag'));
        $this->tags = $this->Q->getArray('tag');
        $this->Field =& $this->Q->getChild('field');
        $this->field = $this->Field->serialize();

        //------
        // axis
        foreach ( array('bid', 'cid') as $key ) {
            if ( !array_key_exists($key, $axis) ) continue;
            $this->_axis[$key] = $axis[$key];
        }

        $this->mid = $mid;
        $this->mbid = $mbid;
    }

    function blogAxis()
    {
        $axis = $this->_axis['bid'];
        return empty($axis) ? 'self' : $axis;
    }

    function categoryAxis()
    {
        $axis = $this->_axis['cid'];
        return empty($axis) ? 'self' : $axis;
    }

    function fire()
    {
        //----------------
        // module link
        $className = str_replace(array('ACMS_GET_', 'ACMS_User_GET_'), '', get_class($this));
        $config = 'config_' . strtolower(preg_replace('@(?<=[a-zA-Z0-9])([A-Z])@', '-$1', $className));
        $bid = !empty($this->mbid) ? $this->mbid : BID;

        $url = acmsLink(array(
            'bid' => $bid,
            'admin' => $config,
            'query' => array(
                'mid' => $this->mid,
                'setid' => Config::getCurrentConfigSetId(),
            ),
        ), false);

        $this->tpl = str_replace(array(
            '{admin_module_bid}',
            '{admin_module_mid}',
            '{admin_module_url}',
            '{admin_module_name}',
            '{admin_module_identifier}',
        ), array(
            $bid,
            $this->mid,
            $url,
            $className,
            $this->identifier,
        ), $this->tpl);

        if ( isSessionAdministrator() ) {
            $this->tpl = preg_replace('@<!--[\t 　]*(BEGIN|END)[\t 　]module_setting[\t 　]-->@', '', $this->tpl);
        }

        //----------------
        // execute & hook
        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('beforeGetFire', array(&$this->tpl, $this));
            $rendered = $this->cache();
            $Hook->call('afterGetFire', array(&$rendered, $this));
        } else {
            $rendered = $this->cache();
        }
        return $rendered;
    }

    function cache() {
        $cacheOn = $this->cache > 0 && $this->identifier;
        if ($cacheOn) {
            $cache = Cache::module();
            $className = str_replace(array('ACMS_GET_', 'ACMS_User_GET_'), '', get_class($this));
            $cacheKey = md5($className . $this->identifier);
            if ($cache->has($cacheKey)) {
                return $cache->get($cacheKey);
            }
        }
        $rendered = $this->get();
        if ($cacheOn) {
            $cache->put($cacheKey, $rendered, $this->cache * 60);
        }
        return $rendered;
    }

    function get()
    {
        return false;
    }

    function checkShortcut($action, $admin, $idKey, $id)
    {
        $admin = str_replace('/', '_', $admin);

        $aryAuth = array();
        if ( sessionWithContribution() ) $aryAuth[] = 'contribution';
        if ( sessionWithCompilation() ) $aryAuth[] = 'compilation';
        if ( sessionWithAdministration() ) $aryAuth[] = 'administration';

        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('dashboard');
        $SQL->setSelect('dashboard_key');
        $SQL->addWhereOpr('dashboard_key', 'shortcut_' . $idKey . '_' . $id . '_' . $admin . '_auth');
        $SQL->addWhereIn('dashboard_value', $aryAuth);
        $SQL->addWhereOpr('dashboard_blog_id', BID);

        return !!$DB->query($SQL->get(dsn()), 'one');
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildModuleField(& $Tpl)
    {
        Tpl::buildModuleField($Tpl, $this->mid, $this->showField);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildDate($datetime, & $Tpl, $block = array(), $prefix = 'date#')
    {
        return Tpl::buildDate($datetime, $Tpl, $block, $prefix);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildField($Field, & $Tpl, $block = array(), $scp = null, $root_vars = array())
    {
        return Tpl::buildField($Field, $Tpl, $block, $scp, $root_vars);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildInputTextValue($data, & $Tpl, $block = array())
    {
        return Tpl::buildInputTextValue($data, $Tpl, $block);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildInputCheckboxChecked($data, & $Tpl, $block = array())
    {
        return Tpl::buildInputCheckboxChecked($data, $Tpl, $block);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildSelectSelected($data, & $Tpl, $block = array())
    {
        return Tpl::buildSelectSelected($data, $Tpl, $block);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildPager($page, $limit, $amount, $delta, $curAttr, & $Tpl, $block = array(), $Q = array())
    {
        return Tpl::buildPager($page, $limit, $amount, $delta, $curAttr, $Tpl, $block, $Q);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildSummaryFulltext(& $vars, $eid, $eagerLoadingData)
    {
        $vars = Tpl::buildSummaryFulltext($vars, $eid, $eagerLoadingData);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildTag(& $Tpl, $eid)
    {
        return Tpl::buildTag($Tpl, $eid);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildImage(& $Tpl, $pimageId, $config, $eagerLoadingData)
    {
        return Tpl::buildImage($Tpl, $pimageId, $config, $eagerLoadingData);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildRelatedEntries(& $Tpl, $eids = array(), $block = array())
    {
        return Tpl::buildRelatedEntries($Tpl, $eids, $block, $this->start, $this->end);
    }

    /**
     * ToDo: deplicated mehod Ver. 2.7.0
     */
    function buildSummary(& $Tpl, $row, $count, $gluePoint, $config, $extraVars = array(), $eagerLoadingData = array())
    {
        return Tpl::buildSummary($Tpl, $row, $count, $gluePoint, $config, $extraVars, $this->page, $eagerLoadingData);
    }
}
