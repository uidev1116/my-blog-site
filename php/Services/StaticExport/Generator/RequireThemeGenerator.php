<?php

namespace Acms\Services\StaticExport\Generator;

use Symfony\Component\Finder\Finder;

class RequireThemeGenerator extends ThemeGenerator
{
    /**
     * @var array
     */
    protected $includeList = array();

    protected function getName()
    {
        return '必須テンプレートの書き出し';
    }

    public function setIncludeList($list)
    {
        $this->includeList = $list;
    }

    /**
     * @return void
     */
    protected function main()
    {
        if ( !$this->sourceTheme ) {
            throw new \RuntimeException('no selected source theme.');
        }
        $includeList = array();
        foreach ($this->includeList as $path) {
            if (!empty($path)) {
                $includeList[] = $path;
            }
        }
        if (count($includeList) > 0) {
            $finder = new Finder();
            $iterator = $finder->in($this->sourceTheme);
            foreach ($includeList as $path) {
                $iterator->path($path);
            }
            $iterator->files();
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
    }
}
