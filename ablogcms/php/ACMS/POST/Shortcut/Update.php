<?php

declare(strict_types=1);

use Acms\Services\Facades\Application;
use Acms\Services\Shortcut\Repository;
use Acms\Services\Shortcut\Helper;

class ACMS_POST_Shortcut_Update extends ACMS_POST
{
    public $isCacheDelete = false;

    /**
     * @var Repository
     */
    protected $ShortcutRepository;

    /**
     * @var Helper
     */
    protected $ShortcutService;

    public function post()
    {
        $this->ShortcutRepository = Application::make('shortcut.repository');
        $this->ShortcutService = Application::make('shortcut.helper');

        $shortcut = $this->extract('shortcut');
        $this->validate($shortcut);

        if (!$this->Post->isValidAll()) {
            AcmsLogger::info('ショートカットの更新に失敗しました', $shortcut->_aryV);
            return $this->Post;
        }

        $admin = $this->Get->get('admin');
        $ids = $this->ShortcutService->createIdsFromGetParameter($this->Get);
        $shortcutKey = $this->ShortcutService->createShortcutKey($admin, $ids);
        $Shortcut = $this->ShortcutRepository->findOneByKey($shortcutKey);
        if (is_null($Shortcut)) {
            $shortcut->setValidator('shortcut', 'exists', false);
            AcmsLogger::info('ショートカットの更新に失敗しました', $shortcut->_aryV);
            return $this->Post;
        }
        $Shortcut->setName($shortcut->get('name'));
        $Shortcut->setAuth($shortcut->get('auth'));

        $this->ShortcutRepository->save($Shortcut);
        $this->Post->set('edit', 'update');

        AcmsLogger::info(
            'ショートカット「' . $Shortcut->getName() . '」を更新しました',
            json_decode(json_encode($Shortcut), true)
        );
        return $this->Post;
    }

    /**
     * @param \Field_Validation $shortcut
     */
    protected function validate(\Field_Validation $shortcut)
    {
        $shortcut->setMethod('name', 'required');
        $shortcut->setMethod('auth', 'required');
        $shortcut->setMethod('shortcut', 'operable', sessionWithAdministration());
        $shortcut->setMethod('shortcut', 'adminIsNull', !empty($this->Get->get('admin')));
        $shortcut->validate(new ACMS_Validator());
    }
}
