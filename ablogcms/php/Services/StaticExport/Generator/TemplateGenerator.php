<?php

namespace Acms\Services\StaticExport\Generator;

use Acms\Services\StaticExport\Contracts\Generator;
use Acms\Services\Facades\Storage;

class TemplateGenerator extends Generator
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var int
     */
    protected $numberOfTasks;

    /**
     * @param mixed $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    protected function getName()
    {
        return '部分テンプレートの書き出し' . '( ' . $this->path . ' )';
    }

    protected function getTasks()
    {
        return 1;
    }

    /**
     * @return void
     */
    protected function main()
    {
        if (!$this->path) {
            throw new \RuntimeException('no selected path.');
        }

        $this->logger->start($this->getName(), $this->getTasks());

        $url = acmsLink(array('bid' => BID), false);
        try {
            $this->request($url . $this->path, $this->path);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $url);
        }
    }

    /**
     * @param string $data
     * @param string $code
     * @param object $info
     * @return void
     */
    protected function callback($data, $code, $info)
    {
        if ($this->logger) {
            $this->logger->processing();
        }
        if (empty($data) || $code != '200') {
            $this->logger->error('データの取得に失敗しました。', $info, $code);
            return;
        }
        try {
            Storage::put($this->destination->getDestinationPath() . $this->destination->getBlogCode() . $info, $data);
        } catch (\Exception $e) {
            $this->logger->error('データの書き込みに失敗しました。', $this->destination->getDestinationPath() . $info);
        }
    }
}
