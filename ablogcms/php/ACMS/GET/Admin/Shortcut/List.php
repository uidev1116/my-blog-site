<?php

declare(strict_types=1);

use Acms\Services\Facades\Application;
use Acms\Services\Shortcut\Repository;
use Acms\Services\Shortcut\Helper;
use Acms\Services\Shortcut\Entities\Shortcut;

class ACMS_GET_Admin_Shortcut_List extends ACMS_GET_Admin
{
    /**
     * @var Repository
     */
    protected $ShortcutRepository;

    /**
     * @var Helper
     */
    protected $ShortcutService;

    public function get()
    {
        /** @var Repository $ShortcutRepository */
        $this->ShortcutRepository = Application::make('shortcut.repository');
        /** @var Helper $ShortcutService */
        $this->ShortcutService = Application::make('shortcut.helper');

        if (!sessionWithContribution()) {
            return '';
        }

        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $Shortcuts = $this->ShortcutRepository->findByAuthorities($this->ShortcutService->getAuthorities(), BID);
        if (empty($Shortcuts)) {
            $this->buildNotFround($Tpl);
            return $Tpl->get();
        }

        return $Tpl->render([
            'shortcut' => $this->buildShortcuts($Shortcuts)
        ]);
    }

    /**
     * ショートカットの組み立て
     *
     * @param Shortcut[] $Shortcuts
     * @return array
     */
    protected function buildShortcuts(array $Shortcuts)
    {
        return array_map(
            function (Shortcut $Shortcut) {
                return [
                    'admin' => $Shortcut->getAdmin(),
                    'name' => $Shortcut->getName(),
                    'url' => $this->ShortcutService->createUrl(
                        $Shortcut->getAdmin(),
                        $Shortcut->getIds()
                    )
                ];
            },
            $Shortcuts
        );
    }

    /**
     * NotFoundテンプレートの組み立て
     *
     * @return void
     */
    protected function buildNotFround(Template $Tpl)
    {
        $Tpl->add('notFound');
    }
}
