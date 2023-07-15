<?php

namespace Acms\Custom\GET;

use ACMS_GET;
use Template;
use ACMS_Corrector;
use Acms\Services\Facades\Storage;

class Vite extends ACMS_GET
{
    public function get()
    {
        $tpl = new Template($this->tpl, new ACMS_Corrector());

        return $tpl->render(array_merge(
            [
                'useDevServer' => $this->useDevServer(),
                'devServerUrl' => env('VITE_DEV_SERVER_URL'),
            ],
            $this->useDevServer() ? [] : [
                'manifest' => $this->getManifest()
            ]
        ));
    }

    protected function useDevServer(): bool
    {
        return env('VITE_ENVIRONMENT', 'development') === 'development';
    }

    protected function getManifest(): \stdClass
    {
        $path = findTemplate(env('VITE_MANIFEST_PATH'));
        try {
            $manifest = Storage::get($path);
            if (empty($manifest)) {
                throw new \RuntimeException('Empty Manifest.');
            }
        } catch (\Exception $e) {
            return new \stdClass();
        }
        return json_decode($manifest);
    }
}
