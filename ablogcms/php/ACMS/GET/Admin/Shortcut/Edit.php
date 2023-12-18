<?php

declare(strict_types=1);

use Acms\Services\Facades\Application;
use Acms\Services\Shortcut\Repository;
use Acms\Services\Shortcut\Helper;

class ACMS_GET_Admin_Shortcut_Edit extends ACMS_GET_Admin_Edit
{
    /**
     * @var Repository
     */
    protected $ShortcutRepository;

    /**
     * @var Helper
     */
    protected $ShortcutService;

    /**
     * @return bool
     */
    protected function validate()
    {
        if ('shortcut_edit' !== ADMIN) {
            return false;
        }
        if (empty($this->Get->get('admin'))) {
            return false;
        }

        return true;
    }

    public function edit(&$Tpl)
    {
        /** @var Repository $ShortcutRepository */
        $this->ShortcutRepository = Application::make('shortcut.repository');
        /** @var Helper $ShortcutService */
        $this->ShortcutService = Application::make('shortcut.helper');

        if (!$this->validate()) {
            return false;
        }

        $admin  = $this->Get->get('admin');

        $ids = $this->ShortcutService->createIdsFromGetParameter($this->Get);

        $shortcutKey = $this->ShortcutService->createShortcutKey($admin, $ids);
        $shortcut = $this->Post->getChild('shortcut');
        $Shortcut = $this->ShortcutRepository->findOneByKey($shortcutKey);

        $shortcut->set('url', $this->ShortcutService->createUrl($admin, $ids));

        if (is_null($Shortcut)) {
            // データがない場合は新規追加 or 削除後
            $this->edit = ACMS_POST ? $this->edit : 'insert';
            return true;
        }

        $this->edit = 'update';
        $shortcut->set('name', $Shortcut->getName());
        $shortcut->set('auth', $Shortcut->getAuth());

        return true;
    }
}
