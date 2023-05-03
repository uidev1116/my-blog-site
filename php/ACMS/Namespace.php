<?php

class ACMS_Namespace
{
    /**
     * @var ACMS_Namespace
     */
    private static $_INSTANCE;

    /**
     * @static
     * @return ACMS_Namespace
     */
    static public function singleton()
    {
        if (self::$_INSTANCE === null) {
            self::$_INSTANCE = new self();
        }
        return self::$_INSTANCE;
    }

    /**
     * @var array
     */
    private $_namespaces = array();

    /**
     * @param string $path
     * @param string $type
     */
    public function addNamespace($path, $type = 'old')
    {
        $this->_namespaces[] = array(
            "path" => $path,
            "type" => $type,
        );
    }

    /**
     * @param string $method
     * @param string $moduleName
     * @return string|boolean
     */
    public function getModuleClass($method, $moduleName)
    {
        foreach ($this->_namespaces as $ns) {
            $type = $ns['type'];
            $path = $ns['path'];

            $moduleClassName = implode('_', array($path, $method, $moduleName));
            if ($type !== 'old') {
                $moduleClassName = str_replace('_', '\\', $moduleClassName);
            }
            if (class_exists($moduleClassName)) {
                return $moduleClassName;
            }
        }
        return false;
    }
}