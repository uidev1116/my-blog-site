<?php

namespace Acms\Services\View;

class Engine implements Contracts\ViewInterface
{
    /**
     * @var array
     */
    protected $_tokens = array();

    /**
     * @var array
     */
    protected $_blockIdLabel = array();

    /**
     * @var array
     */
    protected $_blockLabelId = array();

    /**
     * @var array
     */
    protected $_blockIdTokenBegin = array();

    /**
     * @var array
     */
    protected $_blockIdTokenEnd = array();

    /**
     * @var array
     */
    protected $_blockTokenIdBegin = array();

    /**
     * @var array
     */
    protected $_blockTokenIdEnd = array();

    /**
     * @var array
     */
    protected $_blockIdTxt = array(0 => null);

    /**
     * @var array
     */
    protected $_blockEmptyToken = array();

    /**
     * @var array
     */
    protected $_blockIdEmptyId = array();

    /**
     * @var array
     */
    protected $_varIdLabel = array();

    /**
     * @var array
     */
    protected $_varLabelId = array();

    /**
     * @var array
     */
    protected $_varIdOption = array();

    /**
     * @var array
     */
    protected $_varIdToken = array();

    /**
     * @var array
     */
    protected $_varTokenId = array();

    /**
     * @var array
     */
    protected $_resolvedVarPt = array();

    /**
     * @var \ACMS_Corrector
     */
    protected $_Corrector = null;

    /**
     * テンプレートの初期化
     *
     * @param string $txt
     * @param \ACMS_Corrector $Corrector
     *
     * @return bool
     */
    public function init($txt, $Corrector = null)
    {
        if (is_object($Corrector) and method_exists($Corrector, 'correct')) {
            $this->_Corrector =& $Corrector;
        }

        $txt = $this->fairing($txt); // テンプレートを整形
        $tokens = $this->split($txt); // tokenに分割
        $tokens = $this->validate($tokens); // tokenの整形
        $this->extract($tokens); // 各要素の抽出
        $this->emptyToken();  // empty要素の処理

        // post_max_size を超えたアクセス
        if (empty($_POST) && $_SERVER["REQUEST_METHOD"] === "POST") {
            $this->add('post:v#filesize');
        }

        return $this;
    }

    /**
     * テンプレートを文字列で取得する
     *
     * @return string
     */
    public function get()
    {
        if (is_null($this->_blockIdTxt[0])) {
            $this->add();
        }
        return str_replace(array('<!-- BEGIN\\','<!-- END\\'), array('<!-- BEGIN','<!-- END'), $this->_blockIdTxt[0]);
    }

    /**
     * テンプレートを組み立て文字列で取得する
     *
     * @param object|array $vars
     *
     * @return string
     */
    public function render($vars)
    {
        $this->build(json_decode(json_encode($vars)));
        return $this->get();
    }

    /**
     * @inheritdoc
     */
    public function add($blocks = array(), $vars = array())
    {
        if (null != $this->_blockIdTxt[0]) {
            trigger_error('root is already touched.', E_USER_NOTICE);
            return false;
        }

        if (!is_array($blocks)) {
            $blocks = is_string($blocks) ? array($blocks) : array();
        }
        $blocks = array_reverse($blocks);
        if (!is_array($vars)) {
            $vars = array();
        }

        $pointAry = array();
        $endBlock = end($blocks);
        $parentAry = array();

        foreach ($blocks as $block) {
            if (!isset($this->_blockLabelId[$block])) {
                return false;
            }
            $tempParent = array();
            $ids = $this->_blockLabelId[$block];

            foreach ($ids as $id) {
                $layered = false;
                foreach ($parentAry as $ppt) {
                    if (
                        1
                        && $this->_blockIdTokenEnd[$ppt] >= $this->_blockIdTokenEnd[$id]
                        && $this->_blockIdTokenBegin[$ppt] < $this->_blockIdTokenBegin[$id]
                    ) {
                        $layered = true;
                        $tempParent[] = $id;
                        continue;
                    }
                }
                if ((count($blocks) === 1 || $layered) && $block === $endBlock) {
                    $pointAry[] = $id;
                }
            }
            $parentAry = empty($parentAry) ? $ids : $tempParent;
        }
        if (empty($blocks) && empty($pointAry)) {
            $pointAry = array(0);
        }

        foreach ($pointAry as $pt) {
            $begin = $this->_blockIdTokenBegin[$pt];
            $end = $this->_blockIdTokenEnd[$pt];

            $this->variable($vars, $begin, $end);
            $this->touchBlock($vars, $pt, $begin, $end);

            // init tokens
            for ($i = $begin; $i <= $end; $i++) {
                if (isset($this->_varTokenId[$i])) {
                    $this->_tokens[$i]  = null;
                }
            }
        }
    }

    /**
     * 変数を解決する
     *
     * @param array $vars
     * @param int $begin
     * @param int $end
     *
     * @return void
     */
    protected function variable($vars, $begin, $end)
    {
        foreach ($vars as $key => $value) {
            if (empty($this->_varLabelId[$key])) {
                continue;
            }
            $ids    = $this->_varLabelId[$key];
            foreach ($ids as $id) {
                $token  = $this->_varIdToken[$id];
                if ($begin < $token and $token < $end) {
                    $val = $value;
                    if (isset($this->_Corrector)) {
                        if (isset($this->_varIdOption[$id])) {
                            $correctorOption = preg_replace_callback('/%%([^%]+)%%/', function ($matches) use ($vars) {
                                if (isset($vars[$matches[1]])) {
                                    return $vars[$matches[1]];
                                }
                                return '';
                            }, $this->_varIdOption[$id], 5);
                        } else {
                            $correctorOption = '';
                        }
                        $val = $this->_Corrector->correct($value, $correctorOption, $key);
                    }
                    $this->_tokens[$token]  = strval($val);
                }
            }
        }
    }

    /**
     * ブロックを解決する
     *
     * @param array $vars
     * @param int $pt
     * @param int $begin
     * @param int $end
     *
     * @return void
     */
    protected function touchBlock($vars, $pt, $begin, $end)
    {
        $active     = array($pt => true);
        $ids        = array();
        $buf        = array();
        for ($i = $begin; $i <= $end; $i++) {
            if (isset($this->_blockTokenIdBegin[$i])) {
                array_unshift($ids, $this->_blockTokenIdBegin[$i]);
            }
            $id = $ids[0];

            if ($begin === $end) {
                $this->_resolvedVarPt[$pt] = true;
            }
            if (!empty($active[$id])) {
                $this->_blockIdTxt[$pt] .= $this->_tokens[$i];
            } else {
                if (isset($this->_resolvedVarPt[$id])) {
                    $this->_resolvedVarPt[$pt] = true;
                }
                if (null !== $this->_blockIdTxt[$id] && (isset($this->_resolvedVarPt[$id]) || empty($buf))) {
                    $txt    = '';
                    foreach ($buf as $tokenId => $token) {
                        if (isset($this->_blockTokenIdBegin[$tokenId])) {
                            $active[$this->_blockTokenIdBegin[$tokenId]]    = true;
                        }
                        $txt    .= $token;
                    }

                    $this->_blockIdTxt[$pt] .= $txt;
                    $buf    = array();

                    $this->_blockIdTxt[$pt] .= $this->_blockIdTxt[$id];
                    $this->_blockIdTxt[$id] = null;
                    $i      = $this->_blockIdTokenEnd[$id];

                    array_shift($ids);
                    continue;
                } elseif (isset($this->_blockTokenIdEnd[$i])) {
                    $blockL = $this->_blockIdLabel[$id];
                    if (
                        1
                        && substr($blockL, -6) === ':empty'
                        && !isset($vars[substr($blockL, 0, -6)])
                        && isset($this->_blockIdEmptyId[$i])
                        && $this->_blockIdEmptyId[$i][0] === $begin
                        && $this->_blockIdEmptyId[$i][1] === $end
                    ) {
                        $this->_blockIdTxt[$pt] .= $this->_tokens[$i];
                    } else {
                        for ($j = $this->_blockIdTokenBegin[$id]; $j < $i; $j++) {
                            unset($buf[$j]);
                        }
                    }
                    array_shift($ids);
                    continue;
                }
                $buf[$i]    = $this->_tokens[$i];
            }
            if (isset($this->_varTokenId[$i])) {
                if (null !== $this->_tokens[$i]) {
                    $txt    = '';
                    foreach ($buf as $tokenId => $token) {
                        if (isset($this->_blockTokenIdBegin[$tokenId])) {
                            $active[$this->_blockTokenIdBegin[$tokenId]]    = true;
                        }
                        $txt    .= $token;
                    }
                    $this->_blockIdTxt[$pt] .= $txt;
                    $this->_resolvedVarPt[$pt] = true;
                    $buf    = array();
                }
            }
            if (isset($this->_blockTokenIdEnd[$i])) {
                array_shift($ids);
            }
        }
    }

    /**
     * オブジェクトからテンプレートを組み立てる
     *
     * @param \stdClass $obj
     * @param array $blocks
     *
     * @return void
     */
    protected function build($obj, $blocks = array())
    {
        $strVars = array();
        if (!($obj instanceof \stdClass)) {
            return;
        }

        foreach (get_object_vars($obj) as $key => $vars) {
            if (is_object($vars)) {
                $this->build(json_decode(json_encode($vars)), array_merge(array($key), $blocks));
            } elseif (is_array($vars)) {
                foreach ($vars as $i => $loopVars) {
                    if ($i > 0) {
                        $this->add(array_merge(array($key . ':glue', $key . ':loop'), $blocks));
                    }
                    if (is_object($loopVars)) {
                        /** @var \stdClass $loopObj */
                        $loopObj = json_decode(json_encode($loopVars));
                        $loopObj->{$key . '.i'} = ++$i;
                        $this->build($loopVars, array_merge(array($key . ':loop'), $blocks));
                    } else {
                        $loopObj = new \stdClass();
                        $loopObj->{$key} = $loopVars;
                        $loopObj->{$key . '.i'} = ++$i;
                        $this->build($loopObj, array_merge(array($key . ':loop'), $blocks));
                    }
                }
            } else {
                $strVars[$key] = $vars;
            }
        }
        if (empty($blocks)) {
            $blocks = null;
        }
        $this->add($blocks, $strVars);
    }

    /**
     * テンプレートの整形
     *
     * @param string $txt
     *
     * @return string
     */
    protected function fairing($txt)
    {
        $txt = preg_replace(array(
            '@<!--[\t 　]*[BEGIN]{3,6}+[\t 　]+([^\t 　]+)[\t 　]*-->@',
            '@<!--[\t 　]*[END]{2,4}+[\t 　]+([^\t 　]+)[\t 　]*-->@',
        ), array(
            '<!-- BEGIN $1 -->',
            '<!-- END $1 -->',
        ), $txt);
        $txt = preg_replace_callback('@(?<!\\\)\{([^}\n]+)(?<!\\\)\}\[([^\]\n]+)\]@', function ($matches) {
            return '<!--%' . $matches[1] . '%-->' . str_replace(array('{','}'), array('%%','%%'), $matches[2]) . ' -->';
        }, $txt);
        $txt = preg_replace('@(?<!\\\)\{([^}\n]+)(?<!\\\)\}@', '<!--%$1 -->', $txt);
        $txt = str_replace(array('\{','\}'), array('{','}'), $txt);


        return $txt;
    }

    /**
     * テンプレートの分割
     *
     * @param string $txt
     *
     * @return array
     */
    protected function split($txt)
    {
        $tokens = preg_split(
            '@(<!-- BEGIN |<!-- END | -->|<!--%|%-->)@',
            $txt,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        return $tokens;
    }

    /**
     * 分割されたテンプレートの整形
     *
     * @param array $tokens
     *
     * @return array
     */
    protected function validate($tokens)
    {
        $labels = array();
        $cnt = count($tokens);
        for ($i = 0; $i < $cnt; $i++) {
            $token = $tokens[$i];
            if ('<!-- BEGIN ' == $token) {
                $label = $tokens[$i + 1];
                if (isset($labels[$label])) {
                    unset($tokens[$i]);
                    unset($tokens[$i + 1]);
                    unset($tokens[$i + 2]);
                } else {
                    $labels[$label] = $i;
                }
                $i  += 2;
            } elseif ('<!-- END ' == $token) {
                $label  = $tokens[$i + 1];
                if (!isset($labels[$label])) {
                    unset($tokens[$i]);
                    unset($tokens[$i + 1]);
                    unset($tokens[$i + 2]);
                } else {
                    $from   = $labels[$label];
                    $to     = $i;
                    unset($labels[$label]);
                    foreach ($labels as $_label => $pos) {
                        if ($from < $pos and $pos < $to) {
                            unset($tokens[$pos]);
                            unset($tokens[$pos + 1]);
                            unset($tokens[$pos + 2]);
                            unset($labels[$_label]);
                        }
                    }
                    unset($labels[$label]);
                }
                $i += 2;
            }
        }
        return $tokens;
    }

    /**
     * 各要素の抽出
     *
     * @param array $tokens
     *
     * @return void
     */
    protected function extract($tokens)
    {
        $i          = 1;
        $blockId    = 1;
        $varId      = 0;
        $this->_tokens[0]           = '';
        $this->_blockIdTokenBegin[0] = 0;
        while (null !== ($token = array_shift($tokens))) {
            if ('<!-- BEGIN ' == $token) {
                $label  = array_shift($tokens);
                array_shift($tokens);
                if (substr($label, -6) === ':empty') {
                    $this->_blockEmptyToken[$i] = $label;
                }
                $this->_blockIdTxt[$blockId] = null;
                $this->_blockIdLabel[$blockId] = $label;
                $this->_blockLabelId[$label][] = $blockId;
                $this->_blockIdTokenBegin[$blockId] = $i;
                $blockId++;
                continue;
            } elseif ('<!-- END ' == $token) {
                $label  = array_shift($tokens);
                array_shift($tokens);

                $ids    = $this->_blockLabelId[$label];
                $this->_blockIdTokenEnd[end($ids)] = ($i - 1);
                continue;
            } elseif ('<!--%' == $token) {
                $label  = array_shift($tokens);

                $this->_varIdToken[$varId] = $i;
                $this->_varIdLabel[$varId] = $label;
                $this->_varLabelId[$label][] = $varId;

                if ('%-->' == array_shift($tokens)) {
                    $this->_varIdOption[$varId] = array_shift($tokens);
                    array_shift($tokens);
                }
                $token  = null;

                $varId++;
            }
            $this->_tokens[$i++] = $token;
        }
        $this->_tokens[$i]  = '';
        $this->_blockIdTokenEnd[0] = $i;

        $this->_blockTokenIdBegin = array_flip($this->_blockIdTokenBegin);
        $this->_blockTokenIdEnd = array_flip($this->_blockIdTokenEnd);
        $this->_varTokenId = array_flip($this->_varIdToken);
    }

    /**
     * empty要素の処理
     *
     * @param array $tokens
     *
     * @return void
     */
    protected function emptyToken()
    {
        foreach ($this->_blockIdTokenEnd as $j => $_end) {
            $_begin = $this->_blockIdTokenBegin[$j];
            foreach ($this->_blockEmptyToken as $k => $v) {
                if ($_begin < $k && $k < $_end) {
                    $this->_blockIdEmptyId[$k] = array($_begin, $_end);
                    unset($this->_blockEmptyToken[$k]);
                }
            }
        }
    }
}
