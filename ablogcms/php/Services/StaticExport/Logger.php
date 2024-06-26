<?php

namespace Acms\Services\StaticExport;

use Acms\Services\Facades\Storage;
use AcmsLogger;

class Logger
{
    /**
     * @var string
     */
    protected $destinationPath = '';

    /**
     * @var \Acms\Services\StaticExport\TerminateCheck
     */
    protected $terminateFlag;

    /**
     * @var array
     */
    protected $processList = [];

    /**
     * @var string
     */
    protected $processingName = '';

    /**
     * @var int
     */
    protected $max = 0;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var string
     */
    protected $current;

    /**
     * @var array{
     *   path: string
     * }[]
     */
    protected $removedFiles = [];

    /**
     * @param string $path
     * @param \Acms\Services\StaticExport\TerminateCheck $terminate_flag
     */
    public function init($path, $terminate_flag)
    {
        if (!Storage::isWritable(dirname($path))) {
            throw new \RuntimeException($path . ' is not writable.');
        }
        $this->destinationPath = $path;
        $this->terminateFlag = $terminate_flag;
    }

    public function getDestinationPath()
    {
        return $this->destinationPath;
    }

    public function initLog()
    {
        $this->terminateFlag->removeFlag();
        $data = $this->build();
        $json = json_encode($data);

        Storage::put($this->destinationPath, $json);
    }

    public function destroy()
    {
        Storage::remove($this->destinationPath);
    }

    public function start($name, $max = 1)
    {
        $this->processingName = $name;
        $this->max = $max;
        $this->count = 0;
        $this->processList[] = [
            'message' => $name,
        ];
    }

    public function processing($current = '')
    {
        $this->count++;
        $this->current = $current;
        $data = $this->build();

        $json = json_encode($data);
        Storage::put($this->destinationPath, $json);

        $this->terminateFlag->check();
    }

    public function error($message, $path = '', $code = null)
    {
        $this->errors[] = [
            'message' => $message,
            'path' => $path,
            'code' => $code,
        ];

        $data = $this->build();

        AcmsLogger::debug('静的書き出しログ', $data);

        $json = json_encode($data);
        Storage::put($this->destinationPath, $json);
    }

    public function removedFile(string $path)
    {
        $this->removedFiles[] = [
            'path' => $path,
        ];

        $data = $this->build();

        $json = json_encode($data);
        Storage::put($this->destinationPath, $json);
    }

    protected function build()
    {
        return [
            "inProcess" => $this->processingName,
            "max" => $this->max,
            "count" => $this->count,
            'percentage' => ($this->count > 0 && $this->max > 0) ? intval($this->count / $this->max * 100) : 0,
            'processList' => $this->processList,
            'current' => $this->current,
            'errorList' => $this->errors,
            'removedFiles' => $this->removedFiles
        ];
    }
}
