<?php

use Acms\Services\Common\ValidatorFactory;

class ACMS_Validator
{
    /**
     * @var Acms\Services\Common\ValidatorFactory
     */
    protected $factory;

    /**
     * ACMS_Validator constructor.
     */
    public function __construct()
    {
        $this->factory = ValidatorFactory::singleton();
    }

    public function __call($method, $argument)
    {
        try {
            return $this->factory->call($method, $argument);
        } catch (\Exception $e) {
            return !!$argument[1];
        }
    }
}
