<?php

namespace Acms\Services\React;

use React;

class Request
{
    /**
     * @var React\HttpClient\Request
     */
    protected $request;

    /**
     * @var callable|\Closure|null
     */
    public $updateQueue;

    /**
     * @var callable|\Closure|null
     */
    public $successCallback;

    /**
     * @var string
     */
    protected $responseData;

    /**
     * Request constructor.
     * @param React\HttpClient\Request $request
     * @param callable|\Closure|null $callback
     */
    public function __construct(React\HttpClient\Request $request, $callback = null)
    {
        $this->request = $request;
        $this->successCallback = is_callable($callback) ? $callback : function () {};
    }

    /**
     * get request
     *
     * return void
     */
    public function run()
    {
        $self = $this;
        $this->request->on('response', function (React\HttpClient\Response $response) use ($self) {
            $response->on('data', function ($data) use ($self) {
                $self->responseData .= $data;
            });
            $response->on('end', function ($error, $response) use ($self) {
                $header = $response->getHeaders();
                if ( !isset($header['Content-Length']) ||
                    strlen($self->responseData) == $header['Content-Length']
                ) {
                    call_user_func($self->updateQueue, $response->getCode());
                    call_user_func($self->successCallback, $self->responseData, $response->getCode());
                }
            });
            $response->on('error', function ($error, $response) use ($self) {
                call_user_func($self->successCallback, $self->responseData, $response->getCode());
            });
        });
        $this->request->end();
    }

    /**
     * @param callable|\Closure|null $callback
     * @return void
     */
    public function updateQueue($callback)
    {
        if ( !is_callable($callback) ) {
            $callback = function ($code) {};
        }
        $this->updateQueue = $callback;
    }
}