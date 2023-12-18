<?php

namespace Acms\Services\Webhook;

class Template
{
    protected $blocks = array();

    public function render($code, $data)
    {
        $this->blocks = array();
        $args = array();
        foreach ($data as $key => $value) {
            $args[$key] = $this->arrayToObject($value);
        }
        $generated = $this->compileCode($code);
        ob_start() && extract($args, EXTR_SKIP);
        try {
            @eval('?>' . $generated);
        } catch (\Exception $e) {}
        return ob_get_clean();
    }

    /**
     * @param string $key
     * @return string
     */
    protected function fixVarsKey($key)
    {
        return preg_replace('/[^0-9a-zA-Z\_]/', '_', $key);
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function arrayToObject($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        if (array_values($data) === $data) {
            if (count($data) === 1) {
                return $data[0];
            }
            return $data;
        }
        $obj = new \stdClass;
        foreach($data as $k => $v) {
            if(strlen($k)) {
                $k = $this->fixVarsKey($k);
                if(is_array($v)) {
                    $obj->{$k} = $this->arrayToObject($v);
                } else {
                    $obj->{$k} = $v;
                }
            }
        }
        return $obj;
    }

    protected function compileCode($code)
    {
        $code = $this->compileBlock($code);
        $code = $this->compileYield($code);
        $code = $this->compileEscapedEchos($code);
        $code = $this->compileEchos($code);
        $code = $this->compilePHP($code);
        return $code;
    }

    protected function compilePHP($code)
    {
        return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
    }

    protected function compileEchos($code)
    {
        return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1 ?>', $code);
    }

    protected function compileEscapedEchos($code)
    {
        return preg_replace('~\{{{\s*(.+?)\s*\}}}~is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>', $code);
    }

    protected function compileBlock($code)
    {
        preg_match_all('/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is', $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $value) {
            if (!array_key_exists($value[1], $this->blocks)) $this->blocks[$value[1]] = '';
            if (strpos($value[2], '@parent') === false) {
                $this->blocks[$value[1]] = $value[2];
            } else {
                $this->blocks[$value[1]] = str_replace('@parent', $this->blocks[$value[1]], $value[2]);
            }
            $code = str_replace($value[0], '', $code);
        }
        return $code;
    }

    protected function compileYield($code)
    {
        foreach($this->blocks as $block => $value) {
            $code = preg_replace('/{% ?yield ?' . $block . ' ?%}/', $value, $code);
        }
        $code = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code);
        return $code;
    }
}
