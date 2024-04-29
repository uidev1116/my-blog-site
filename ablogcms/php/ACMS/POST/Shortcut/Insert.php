<?php

declare(strict_types=1);

use Acms\Services\Facades\Application;
use Acms\Services\Shortcut\Repository;
use Acms\Services\Shortcut\Helper;

class ACMS_POST_Shortcut_Insert extends ACMS_POST
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

    /**
     * @return \Field_Validation
     */
    public function post()
    {
        $this->ShortcutRepository = Application::make('shortcut.repository');
        $this->ShortcutService = Application::make('shortcut.helper');

        $shortcut = $this->extract('shortcut');
        $this->validate($shortcut);

        if (!$this->Post->isValidAll()) {
            AcmsLogger::info('ショートカットの作成に失敗しました', $shortcut->_aryV);
            return $this->Post;
        }

        $ids = $this->ShortcutService->createIdsFromGetParameter($this->Get);
        $Shortcut = $this->ShortcutService->createShortcut([
            'name' => $shortcut->get('name'),
            'sort' => $this->ShortcutRepository->nextSort(),
            'auth' => $shortcut->get('auth'),
            'action' => $this->Get->get('action'),
            'admin' => $this->Get->get('admin'),
            'ids' => $ids,
            'blogId' => BID
        ]);

        $this->ShortcutRepository->save($Shortcut);
        $this->Post->set('edit', 'insert');

        AcmsLogger::info(
            'ショートカット「' . $Shortcut->getName() . '」を作成しました',
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
