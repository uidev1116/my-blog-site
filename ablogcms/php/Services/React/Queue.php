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
     * @var float
     */
    private const INTERVAL = 0.1;

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
        $this->timer = $this->loop->addPeriodicTimer(self::INTERVAL, function () use ($page404) {
            for ($i = $this->workingCount; $i < $this->workingTableSize; $i++) {
                $request = $this->pop();
                if ($request instanceof Request) {
                    $request->updateQueue(function ($code) use ($page404) {
                        if ($code != '200' && $page404) {
                            $this->loop->cancelTimer($this->timer);
                        }
                        $this->workingCount--;
                    });
                    $this->workingCount++;
                    $request->run();
                }
            }
            if ($this->workingCount < 1) {
                $this->loop->cancelTimer($this->timer);
            }
        });
        $this->loop->run();
    }
}
