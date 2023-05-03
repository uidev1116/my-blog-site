<?php

/**
 * ACMS_Http_File
 *
 */
class ACMS_Http
{
    public static function file($allowExtensions=null)
    {
        return new ACMS_Http_File($allowExtensions);
    }

    public function __construct()
    {

    }
}
