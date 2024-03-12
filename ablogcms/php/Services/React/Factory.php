<?php

namespace Acms\Services\React;

use React;

class Factory
{
    /**
     * @var React\EventLoop\LibEventLoop|React\EventLoop\StreamSelectLoop
     */
    protected $loop;

    /**
     * @var React\HttpClient\Client
     */
    protected $httpClient;

    public function __construct($nameServer = '8.8.8.8')
    {
        $this->loop = React\EventLoop\Factory::create();
        $dnsResolverFactory = new React\Dns\Resolver\Factory();
        $dnsResolver = $dnsResolverFactory->createCached($nameServer, $this->loop);

        $factory = new React\HttpClient\Factory();
        $this->httpClient = $factory->create($this->loop, $dnsResolver);
    }

    /**
     * @param $size
     * @return \Acms\Services\React\Queue
     */
    public function createQueue($size)
    {
        return new Queue($size, $this->loop);
    }

    /**
     * create Request
     *
     * @param string $url
     * @param callable|\Closure|null $callback
     * @return \Acms\Services\React\Request
     */
    public function createRequest($url, $callback = null)
    {
        $request = $this->httpClient->request('GET', $url);

        return new Request($request, $callback);
    }
}
