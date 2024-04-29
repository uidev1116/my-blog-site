<?php

namespace Acms\Services\Webhook;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Exception;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Common;
use LogicException;
use Throwable;

class Template
{
    /**
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * @return void
     * @throws LogicException
     */
    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__);
        $this->twig = new Environment($loader, [
            'cache' => CACHE_DIR . '/webhook-twig'
        ]);
    }

    /**
     * render payload
     *
     * @param string $code
     * @param array $data
     * @return string
     * @throws Throwable
     */
    public function render($code, $data)
    {
        try {
            $template = $this->twig->createTemplate($code);
            return $template->render($data);
        } catch (Exception $e) {
            Logger::warning('Webhookのペイロードのレンダリングに失敗しました。' . $e->getMessage(), Common::exceptionArray($e));
        }
        return '';
    }
}
