<?php

namespace Acms\Services\View;

class ApiEngine implements Contracts\ViewInterface
{
    /**
     * @var \ACMS_Corrector
     */
    protected $_Corrector = null;

    /**
     * @var array
     */
    protected $json = array();

    /**
     * @var array
     */
    protected $blockData = array();

    /**
     * @var array
     */
    protected $childData = array();

    /**
     * @var array
     */
    protected $stackData = array();

    /**
     * テンプレートの初期化
     *
     * @param string $txt
     * @param ACMS_Corrector $Corrector
     *
     * @return bool|self
     */
    public function init($txt, $Corrector = null)
    {
        if (is_object($Corrector) && method_exists($Corrector, 'correct')) {
            $this->_Corrector =& $Corrector;
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
//        $this->blockData = $this->fixStructure($this->blockData);
        return json_encode($this->blockData);
    }

    /**
     * テンプレートを組み立て文字列で取得する
     *
     * @param mixed $vars
     *
     * @return string
     */
    public function render($vars)
    {
        return json_encode($vars);
    }

    /**
     * @inheritdoc
     */
    public function add($blocks = array(), $vars = array())
    {
        if (!is_array($blocks)) {
            $blocks = is_string($blocks) ? array($blocks) : null;
        }
        if (!is_array($vars)) {
            $vars = array();
        }
        if (empty($vars)) {
            $vars = array();
        }

        if (empty($blocks)) {
            foreach ($vars as $key => $val) {
                $this->blockData[$key] = $val;
            }
        } else {
            if (isset($this->stackData[$blocks[0]])) {
                // スタックしていたブロックを取り出し
                $vars = $this->mergeLevel1($vars, $this->stackData[$blocks[0]]);
                unset($this->stackData[$blocks[0]]);
            }
            if (count($blocks) > 1) {
                // ルートブロックでないので、スタック
                if (isset($this->stackData[$blocks[1]])) {
                    $this->stackData[$blocks[1]] = $this->mergeLevel1($this->stackData[$blocks[1]], array($blocks[0] => $vars));
                } else {
                    if (false !== strpos($blocks[1], ':loop')) {
                        $this->stackData[$blocks[1]] = array();
                        $this->stackData[$blocks[1]][] = array($blocks[0] => $vars);
                    } else {
                        $this->stackData[$blocks[1]] = array($blocks[0] => $vars);
                    }
                }
            } else {
                // ルートブロックを処理
                if (isset($this->blockData[$blocks[0]])) {
                    $this->blockData = $this->mergeLevel1($this->blockData, array($blocks[0] => $vars));
                } else {
                    if (false !== strpos($blocks[0], ':loop')) {
                        $this->blockData[$blocks[0]] = array();
                        $this->blockData[$blocks[0]][] = $vars;
                    } else {
                        $this->blockData[$blocks[0]] = $vars;
                    }
                }
            }
        }
    }

    /**
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    private function mergeLevel1($arr1, $arr2)
    {
        foreach ($arr1 as $key => $value) {
            if (isset($arr2[$key])) {
                if (!$this->isVectorArray($arr1[$key])) {
                    $arr1[$key] = array();
                    $arr1[$key][] = $value;
                }
                $arr1[$key][] = $arr2[$key];
                unset($arr2[$key]);
            }
        }
        foreach ($arr2 as $key => $value) {
            $arr1[$key] = $value;
        }
        return $arr1;
    }

    /**
     * @param array $arr
     * @return bool
     */
    private function isVectorArray($arr)
    {
        return array_values($arr) === $arr;
    }

    /**
     * @param array $data
     * @return array
     */
    private function fixStructure($data)
    {
        foreach ($data as $key => $value) {
            if ($this->isVectorArray($value) && count($value) === 1) {
                $data[$key] = $value[0];
            }
        }
        return $data;
    }
}
