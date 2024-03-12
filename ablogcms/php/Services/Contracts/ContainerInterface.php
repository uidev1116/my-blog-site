<?php

namespace Acms\Services\Contracts;

interface ContainerInterface
{
    /**
     * DIコンテナに登録されているか判定
     *
     * @param string $alias
     *
     * @return bool
     */
    public function exists($alias);

    /**
     * DIコンテナに登録されている一覧を取得
     *
     * @return array
     */
    public function aliasList();

    /**
     * get service
     *
     * @param string $alias
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function make($alias);

    /**
     * register service
     *
     * @param string $alias
     * @param string | callable $class
     * @param array $arguments
     *
     * @return void
     */
    public function bind($alias, $class, array $arguments = array());

    /**
     * register service as singleton
     *
     * @param string $alias
     * @param string | callable $class
     * @param array $arguments
     *
     * @return void
     */
    public function singleton($alias, $class, array $arguments = array());

    /**
     * register service bootstrap function
     *
     * @param string $alias
     * @param callable $callback
     */
    public function bootstrap($alias, $callback);

    /**
     * create service
     *
     * @param string $alias
     *
     * @return mixed
     */
    public function bindMake($alias);

    /**
     * create service as singleton
     *
     * @param string $alias
     *
     * @return mixed
     */
    public function singletonMake($alias);

    /**
     * create instance
     *
     * @param string | callable $class
     * @param array $arguments
     *
     * @return mixed
     */
    public function newInstance($class, array $arguments = array());
}
