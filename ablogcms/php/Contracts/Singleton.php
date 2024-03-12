<?php

namespace Acms\Contracts;

class Singleton
{
    /**
     * @var object
     */
    protected static $instance;

    /**
     * @static
     * @return object
     */
    public static function singleton()
    {
        $called = get_called_class();
        if (!isset(self::$instance[$called])) {
            static::$instance[$called] = new $called();
        }
        return self::$instance[$called];
    }

    private function __clone()
    {
        throw new \RuntimeException('Can not create clone.');
    }
}
