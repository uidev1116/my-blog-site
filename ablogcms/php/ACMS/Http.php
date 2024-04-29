<?php

/**
 * ACMS_Http_File
 *
 */
class ACMS_Http
{
    /**
     * @param string $name
     * @return ACMS_Http_File
     */
    public static function file($name)
    {
        return new ACMS_Http_File($name);
    }
}
