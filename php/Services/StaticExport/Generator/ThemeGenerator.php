<?php

namespace Acms\Services\StaticExport\Generator;

use Acms\Services\StaticExport\Contracts\Generator;
use Acms\Services\Facades\Storage;
use Symfony\Component\Finder\Finder;

class ThemeGenerator extends Generator
{
    /**
     * @var string
     */
    protected $sourceTheme;

    /**
     * @var int
     */
    protected $numberOfTasks;

    /**
     * @param mixed $sourceTheme
     */
    public function setSourceTheme($sourceTheme)
    {
        $this->sourceTheme = $sourceTheme;
    }

    protected function getName()
    {
        return 'テンプレートの書き出し';
    }

    protected function getTasks()
    {
        return $this->numberOfTasks;
    }

    /**
     * @return void
     */
    protected function main()
    {
        if ( !$this->sourceTheme ) {
            throw new \RuntimeException('no selected source theme.');
        }
        $finder = new Finder();
        $iterator = $finder
            ->in($this->sourceTheme)
            ->notPath('include')
            ->notPath('admin')
            ->name('/\.(html|htm)$/')
            ->files();

        if (config('forbid_direct_access_tpl') !== 'off') {
            $iterator->notPath(config('forbid_direct_access_tpl'));
            $iterator->notName(config('forbid_direct_access_tpl'));
        }

        $this->numberOfTasks = iterator_count($iterator);
        $this->logger->start($this->getName(), $this->getTasks());

        foreach ($iterator as $file) {
            try {
                $url = acmsLink(array('bid'=>BID), false) . $file->getRelativePathname();
                $this->request($url, $file);
            } catch ( \Exception $e ) {
                $this->logger->error($e->getMessage(), $file->getRelativePathname());
            }
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
        if ( $this->logger ) {
            $this->logger->processing($info->getRelativePathname());
        }
        if ( empty($data) || $code != '200' ) {
            $this->logger->error('データの取得に失敗しました。', $info->getRelativePathname(), $code);
        } else {
            try {
                $destination = $this->destination->getDestinationPath() . $this->destination->getBlogCode();
                Storage::makeDirectory($destination . $info->getRelativePath());
                Storage::put($destination . $info->getRelativePathname(), $data);
            } catch ( \Exception $e ) {
                $this->logger->error('データの書き込みに失敗しました。', $info->getRelativePathname());
            }
        }
    }
}