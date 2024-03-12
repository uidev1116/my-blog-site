<?php

/**
 * ACMS_Http_File
 *
 */
class ACMS_Http
{
    public static function file($name)
    {
        return new ACMS_Http_File($name);
    }
}
