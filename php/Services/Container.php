<?php

namespace Acms\Services;

/**
 * DI Container
 *
 * Class Acms\Services\Container
 */
class Container implements Contracts\ContainerInterface
{
    /**
     * @var array
     */
    private $aliases;

    /**
     * @var array
     */
    protected $bootstrap;

    /**
     * @var array
     */
    private $resolvedInstance;

    /**
     * Container constructor.
     */
    public function __construct()
    {
        $this->aliases = array();
        $this->bootstrap = array();

        $this->singleton('container', '\Acms\Services\Container');
        $this->resolvedInstance['container'] = $this;
    }

    /**
     * DIコンテナに登録されているか判定
     *
     * @param string $alias
     *
     * @return bool
     */
    public function exists($alias)
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * DIコンテナに登録されている一覧を取得
     *
     * @return array
     */
    public function aliasList()
    {
        return $this->aliases;
    }

    /**
     * get service
     *
     * @param string $alias
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function make($alias)
    {
        if ( !$this->exists($alias) ) {
            throw new \RuntimeException('Container does not register ' . $alias);
        }
        $info = $this->aliases[$alias];

        if ( !($info->class instanceof \Closure) && !class_exists($info->class) ) {
            throw new \RuntimeException("Container missing class '" . $info->class . "'.");
        }
        if ( is_callable(array($this, $info->type . 'Make')) ) {
            $instance = call_user_func(array($this, $info->type . 'Make'), $alias);

            if ( isset($this->bootstrap[$alias]) ) {
                $this->bootstrap[$alias]($instance);
            }

            return $this->resolvedInstance[$alias] = $instance;
        }
        throw new \RuntimeException('Failed to make instance');
    }

    /**
     * register service
     *
     * @param string $alias
     * @param string | callable $class
     * @param array $arguments
     *
     * @return void
     */
    public function bind($alias, $class, array $arguments = array())
    {
        $info = (object)array(
            'type' => 'bind',
            'class' => $class,
            'arguments' => $arguments,
        );

        $this->aliases[$alias] = $info;
    }

    /**
     * register service as singleton
     *
     * @param string $alias
     * @param string | callable $class
     * @param array $arguments
     *
     * @return void
     */
    public function singleton($alias, $class, array $arguments = array())
    {
        $info = (object)array(
            'type' => 'singleton',
            'class' => $class,
            'arguments' => $arguments,
        );
        $this->aliases[$alias] = $info;
    }

    /**
     * register service bootstrap function
     *
     * @param string $alias
     * @param callable $callback
     */
    public function bootstrap($alias, $callback)
    {
        if ( !$callback instanceof \Closure ) {
            return;
        }

        $this->bootstrap[$alias] = $callback;
    }

    /**
     * create service
     *
     * @param string $alias
     *
     * @return mixed
     */
    public function bindMake($alias)
    {
        $info = $this->aliases[$alias];

        return $this->newInstance($info->class, $info->arguments);
    }

    /**
     * create service as singleton
     *
     * @param string $alias
     *
     * @return mixed
     */
    public function singletonMake($alias)
    {
        if ( isset($this->resolvedInstance[$alias]) ) {
            return $this->resolvedInstance[$alias];
        }

        $info = $this->aliases[$alias];

        return $this->newInstance($info->class, $info->arguments);
    }

    /**
     * create instance
     *
     * @param string | callable $class
     * @param array $arguments
     *
     * @return mixed
     */
    public function newInstance($class, array $arguments = array())
    {
        if ( $class instanceof \Closure ) {
            $obj = $class($this);
            return $obj;
        }
        $obj = $this->createInstance($class, $arguments);

        return $obj;
    }

    /**
     * 定義されている関数の引数を取得
     *
     * @param callable $closure
     *
     * @return array
     */
    protected function getFunctionArguments($closure)
    {
        $reflection = new \ReflectionFunction($closure);

        return $reflection->getParameters();
    }

    /**
     * 定義されているメソッドの引数を取得
     *
     * @param string | object $class
     * @param string $method
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function getMethodArguments($class, $method)
    {
        if ( is_object($class) ) {
            $class = get_class($class);
        }
        $reflection = new \ReflectionClass($class);
        if ( $function = $reflection->getMethod($method) ) {
            return $function->getParameters();
        }

        throw new \RuntimeException('Method missing ' . $class . ':' . $method);
    }

    /**
     * 定義されているコンストラクタの引数を取得
     *
     * @param string | object $class
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function getConstructorArguments($class)
    {
        if ( is_object($class) ) {
            $class = get_class($class);
        }
        $reflection = new \ReflectionClass($class);
        if ( $constructor = $reflection->getConstructor() ) {
            return $constructor->getParameters();
        }

        throw new \RuntimeException('Constructor missing ' . $class);
    }

    /**
     * 引数リストからインスタンスを生成
     *
     * @param array $arguments \ReflectionParameter の配列
     *
     * @return array
     */
    protected function createInstanceFromArray($arguments)
    {
        $objects = array();

        foreach ( $arguments as $arg ) {
            $name = $arg->name;
            $class = $arg->getClass();
            if ( !$class ) {
                continue;
            }
            $class = $class->name;
            $reflection = new \ReflectionClass($class);
            if ( $this->exists($class) ) {
                $objects[$name] = $this->make($class);
            } else if ( $reflection->isInstantiable() ) {
                $objects[$name] = $this->newInstance($class);
            }
        }

        return $objects;
    }

    /**
     * インスタンスの生成
     *
     * @param string $class
     * @param array $arguments
     *
     * @param array $arguments
     *
     * @return object
     */
    protected function createInstance($class, $arguments = array())
    {
        $reflection = new \ReflectionClass($class);

        if ( empty($arguments) ) {
            $obj = new $class;
        } else {
            $obj = $reflection->newInstanceArgs($arguments);
        }
        if ( !$obj ) {
            throw new \RuntimeException('Failed to make instance');
        }

        return $obj;
    }

    /**
     * 引数リストに、定義されているデフォルト値を設定
     *
     * @param array $args
     *
     * @return array
     */
    private function setDefaultArguments($args)
    {
        $arguments = array();

        foreach ( $args as $param ) {
            try {
                $val = $param->getDefaultValue();
                $arguments[$param->name] = $val;
            } catch ( \ReflectionException $e ) {
                $arguments[$param->name] = null;
            } catch ( \Exception $e ) {
                continue;
            }
        }

        return $arguments;
    }
}