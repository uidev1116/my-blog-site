<?php

namespace Acms\Services\StaticExport\Generator;

use React\Promise\Promise;
use React\Promise\PromiseInterface;
use Symfony\Component\Finder\Finder;

use function React\Async\await;

class RequireThemeGenerator extends ThemeGenerator
{
    /**
     * @var array
     */
    protected $includeList = [];

    protected function getName(): string
    {
        return '必須テンプレートの書き出し ( ' . $this->sourceTheme . ' )';
    }

    /**
     * @param string[] $list
     */
    public function setIncludeList(array $list): void
    {
        $this->includeList = $list;
    }

    /**
     * @inheritDoc
     */
    public function run(): PromiseInterface
    {
        return new Promise(
            function (callable $resolve, callable $reject) {
                if (!$this->sourceTheme) {
                    $reject(new \RuntimeException('no selected source theme.'));
                    return;
                }
                $includeList = [];
                foreach ($this->includeList as $path) {
                    if (!empty($path)) {
                        $includeList[] = $path;
                    }
                }

                if (count($includeList) === 0) {
                    $resolve(null);
                    return;
                }

                $finder = new Finder();
                $iterator = $finder->in($this->sourceTheme);
                foreach ($includeList as $path) {
                    $iterator->path($path);
                }
                $iterator->files();

                $pages = $this->createPages($iterator);
                $this->logger->start($this->getName(), count($pages));

                await($this->handle($pages));
                $resolve(null);
            }
        );
    }
}
