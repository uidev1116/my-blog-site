<?php

/**
 * @mixin \Acms\Services\View\Engine
 */
class Template
{
    /**
     * @var \Acms\Services\View\Contracts\ViewInterface
     */
    protected $engine;

    /**
     * @param string $txt
     * @param null|\ACMS_Corrector $Corrector
     * @return bool
     */
    public function __construct($txt, $Corrector = null)
    {
        $this->engine = View::init($txt, $Corrector);
    }

    public function __call($name, $args)
    {
        if (method_exists($this->engine, $name)) {
            return call_user_func_array([$this->engine, $name], $args); // @phpstan-ignore argument.type
        }
    }
}
