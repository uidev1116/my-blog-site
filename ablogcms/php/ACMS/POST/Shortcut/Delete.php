<?php

declare(strict_types=1);

use Acms\Services\Facades\Application;
use Acms\Services\Shortcut\Repository;
use Acms\Services\Shortcut\Helper;

class ACMS_POST_Shortcut_Delete extends ACMS_POST
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
        /** @var Repository $ShortcutRepository */
        $this->ShortcutRepository = Application::make('shortcut.repository');
        /** @var Helper $ShortcutService */
        $this->ShortcutService = Application::make('shortcut.helper');

        $shortcut = $this->extract('shortcut');
        $this->validate($shortcut);

        if (!$this->Post->isValidAll()) {
            AcmsLogger::info('ショートカットの削除に失敗しました', $shortcut->_aryV);
            return $this->Post;
        }

        $admin = $this->Get->get('admin');
        $ids = $this->ShortcutService->createIdsFromGetParameter($this->Get);

        $shortcutKey = $this->ShortcutService->createShortcutKey($admin, $ids);
        $Shortcut = $this->ShortcutRepository->findOneByKey($shortcutKey);

        if (is_null($Shortcut)) {
            $shortcut->setValidator('shortcut', 'exists', false);
            AcmsLogger::info('ショートカットの削除に失敗しました', $shortcut->_aryV);
            return $this->Post;
        }

        $this->ShortcutRepository->delete($Shortcut);
        $this->Post->set('edit', 'delete');

        AcmsLogger::info(
            'ショートカット「' . $Shortcut->getName() . '」を削除しました',
            json_decode(json_encode($Shortcut), true)
        );
        return $this->Post;
    }

    /**
     * @param \Field_Validation $shortcut
     */
    protected function validate(\Field_Validation $shortcut)
    {
        $shortcut->setMethod('shortcut', 'operable', sessionWithAdministration());
        $shortcut->setMethod('shortcut', 'adminIsNull', !empty($this->Get->get('admin')));
        $shortcut->validate(new ACMS_Validator());
    }
}
