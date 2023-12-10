<?php

namespace Acms\Services\Http;

use AcmsLogger;

class Engine
{
    /**
     * @var array
     */
    protected $responseHeaders;

    /**
     * @var string
     */
    protected $responseBody;

    /**
     * @var \CurlHandle
     */
    protected $curl;

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * init request
     *
     * @param string $uri
     * @param string $method
     *
     * @return self
     */
    public function init($uri, $method = "get")
    {
        $method = strtoupper($method);
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_URL, $uri);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        if (isDebugMode()) {
            curl_setopt($this->curl, CURLOPT_VERBOSE, true);
        }
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true); // Locationを辿る
        curl_setopt($this->curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER , array('Expect:'));
        curl_setopt($this->curl, CURLOPT_TIMEOUT, MAX_EXECUTION_TIME);

        $this->setCurlOption();

        return $this;
    }

    /**
     * set request headers
     *
     * @param array $headers
     */
    public function setRequestHeaders($headers = array())
    {
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * set post data
     *
     * @param array $data
     *
     * @return self
     */
    public function setPostData($data = array())
    {
        curl_setopt($this->curl,CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        return $this;
    }

    /**
     * send request
     *
     * @return self
     */
    public function send()
    {
        $response = curl_exec($this->curl);
        $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);
        curl_close($this->curl);

        if (CURLE_OK !== $errno) {
            AcmsLogger::debug($error, [
                'errno' => $errno,
            ]);
            throw new \RuntimeException($error, $errno);
        }
        $this->responseHeaders = $this->getHeadersFromCurlResponse(substr($response, 0, $header_size));
        $this->responseBody = substr($response, $header_size);

        return $this;
    }

    /**
     * get response header
     *
     * @param string|bool $name
     *
     * @return string|array
     */
    public function getResponseHeader($name = false)
    {
        if ($name === false) {
            return $this->responseHeaders;
        }
        $name = strtolower($name);
        if (isset($this->responseHeaders[$name])) {
            return $this->responseHeaders[$name];
        }
        return false;
    }

    /**
     * get response body
     *
     * @return string
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * get headers from curl response
     *
     * @param $header_string
     *
     * @return array
     */
    protected function getHeadersFromCurlResponse($header_string)
    {
        $headers = array();

        foreach ( preg_split("/(\r|\n|\r\n)/", $header_string) as $i => $line ) {
            if ( $i === 0 ) {
                $headers['http_code'] = $line;
                if ( preg_match('/HTTP\/[1|2]\.[0|1|x] ([0-9]{3})/', $line, $matches) ) {
                    $headers['status_code'] = $matches[1];
                }
            } else if ( strpos($line, ':') !== false ) {
                list ($key, $value) = explode(': ', $line);
                $headers[strtolower($key)] = $value;
            }
        }
        return $headers;
    }

    /**
     * @return mixed
     */
    protected function setCurlOption()
    {
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HEADER, true);
    }
}
