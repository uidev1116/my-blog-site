<?php

namespace Acms\Services\StaticExport\Contracts;

use Acms\Services\Facades\React;
use Acms\Services\StaticExport\Destination;
use Acms\Services\StaticExport\Logger;
use Acms\Services\StaticExport\Compiler as PublishCompiler;

abstract class Generator
{
    /**
     * @var \Acms\Services\StaticExport\Compiler
     */
    protected $compiler;

    /**
     * @var \Acms\Services\React\Queue
     */
    protected $queue;

    /**
     * @var \Acms\Services\StaticExport\Destination
     */
    protected $destination;

    /**
     * @var \Acms\Services\StaticExport\Logger
     */
    protected $logger;

    /**
     * Generator constructor.
     * @param \Acms\Services\StaticExport\Compiler $compiler
     * @param \Acms\Services\StaticExport\Destination $destination
     * @param \Acms\Services\StaticExport\Logger $logger
     * @param int $queue
     */
    public function __construct(PublishCompiler $compiler, Destination $destination, Logger $logger = null, $queue = 5)
    {
        $this->compiler = $compiler;
        $this->destination = $destination;
        $this->queue = React::createQueue($queue);
        $this->logger = $logger;
    }

    /**
     * @param boolean $page404
     * @return void
     */
    public function run($page404 = false)
    {
        $this->main();
        $this->queue->exec($page404);
    }

    /**
     * @return void
     */
    abstract protected function main();

    /**
     * @param string $data
     * @param string $code
     * @param object $info
     * @return void
     */
    abstract protected function callback($data, $code, $info);

    /**
     * @param string $url
     * @param mixed $info
     */
    protected function request($url, $info)
    {
        $self = $this;
        $request = React::createRequest($url, function($data, $code) use($self, $info) {
            if ( !empty($data) && $code == '200' ) {
                $data = $self->compiler->compile($data);
            }
            $self->callback($data, $code, $info);
        });
        $this->queue->push($request);
    }
}