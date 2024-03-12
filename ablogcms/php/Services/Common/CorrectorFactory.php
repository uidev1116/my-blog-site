<?php

namespace Acms\Services\Common;

class CorrectorFactory extends Factory
{
    /**
     * @param string $method
     * @param string $txt
     * @param mixed  $params
     * @param bool $magic_call
     * @return string|bool
     */
    public function call($method, $txt, $params = array(), $magic_call = false)
    {
        if (!is_array($params)) {
            $params = array($params);
        }
        $argument = array($txt, $params);
        if ($magic_call) {
            $argument = $params;
        }
        foreach ($this->_collection as $corrector) {
            if (is_callable(array($corrector, $method))) {
                return call_user_func_array(array($corrector, $method), $argument);
            }
        }
        return false;
    }
}
