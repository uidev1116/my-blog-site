<?php

use Acms\Services\Facades\Application;
use Acms\Services\Facades\Config;

class ACMS_GET_Ajax_Unit extends ACMS_GET
{
    public function get()
    {
        if (!($column = $this->Get->get('column'))) {
            return '';
        }
        [$pfx, $type] = array_pad(explode('-', $column, 2), 2, '');

        $rid = intval($this->Get->get('rid')) ?: null;
        $mid = intval($this->Get->get('mid')) ?: null;
        $setid = intval($this->Get->get('setid')) ?: null;
        if ($mid) {
            $setid = null;
        }
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        $config = $this->loadConfig(BID, $rid, $mid, $setid);

        $renderingService = Application::make('unit-rendering-config');
        assert($renderingService instanceof \Acms\Services\Unit\Rendering\Config);

        $renderingService->render($pfx, $type, $tpl, $config);

        return $tpl->get();
    }

    protected function loadConfig(int $bid, ?int $rid, ?int $mid, ?int $setid): Field
    {
        $config = Config::loadDefaultField();
        if ($setid) {
            $config->overload(Config::loadConfigSet($setid));
        } else {
            $config->overload(Config::loadBlogConfig($bid));
        }
        if ($rid && !$mid) {
            $config->overload(Config::loadRuleConfig($rid, $setid));
        } elseif ($mid) {
            $config->overload(Config::loadModuleConfig($mid, $rid));
        }
        return $config;
    }
}
