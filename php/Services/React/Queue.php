<?php

namespace Acms\Services\React;

use React;

class Queue
{
    /**
     * @var int
     */
    protected $workingTableSize;

    /**
     * @var array
     */
    protected $queue;

    /**
     * @var React\EventLoop\LibEventLoop|React\EventLoop\Timer\Timer
     */
    protected $timer;

    /**
     * @var React\EventLoop\LibEventLoop|React\EventLoop\StreamSelectLoop
     */
    protected $loop;

    /**
     * @var int
     */
    protected $workingCount;

    /**
     * @const float
     */
    const INTERVAL = 0.1;

    /**
     * Queue constructor.
     *
     * @param int $size
     * @param React\EventLoop\LibEventLoop|React\EventLoop\StreamSelectLoop $loop
     */
    public function __construct($size, $loop)
    {
        $this->workingTableSize = $size;
        $this->queue = array();
        $this->workingCount = 0;
        $this->loop = $loop;
    }

    /**
     * @param \Acms\Services\React\Request $request
     */
    public function push(Request $request)
    {
        $this->queue[] = $request;
    }

    /**
     * @return \Acms\Services\React\Request
     */
    public function pop()
    {
        return array_shift($this->queue);
    }

    /**
     * @param boolean $page404
     * @return void
     */
    public function exec($page404 = false)
    {
        $self = $this;
        $self->timer = $self->loop->addPeriodicTimer(self::INTERVAL, function () use ($self, $page404) {
            for ( $i=$self->workingCount; $i<$self->workingTableSize; $i++ ) {
                $request = $self->pop();
                if ( $request instanceof Request ) {
                    $request->updateQueue(function ($code) use ($self, $page404) {
                        if ( $code != '200' && $page404 ) {
                            $self->loop->cancelTimer($self->timer);
                        }
                        $self->workingCount--;
                    });
                    $self->workingCount++;
                    $request->run();
                }
            }
            if ( $self->workingCount < 1 ) {
                $self->loop->cancelTimer($self->timer);
            }
        });
        $this->loop->run();
    }
}
