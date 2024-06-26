<?php

namespace Acms\Services\Common;

class ValidatorFactory extends Factory
{
    /**
     * @param string $method
     * @param mixed  $params
     * @return string|bool
     */
    public function call($method, $params = [])
    {
        if (!is_array($params)) {
            $params = [$params];
        }
        foreach ($this->_collection as $validator) {
            if (is_callable([$validator, $method])) {
                return call_user_func_array([$validator, $method], $params);
            }
        }
        throw new \RuntimeException('Not found validator.');
    }
}
