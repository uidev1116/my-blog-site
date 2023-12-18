<?php

declare(strict_types=1);

use Acms\Services\Facades\Application;
use Acms\Services\Shortcut\Repository;
use Acms\Services\Shortcut\Helper;

class ACMS_POST_Shortcut_Index_Sort extends ACMS_POST
{
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

        $this->validate($this->Post);

        if (!$this->Post->isValidAll()) {
            AcmsLogger::info('ショートカットの表示順の更新に失敗しました', $this->Post->_aryV);
            return $this->Post;
        }

        $Shortcuts = $this->ShortcutRepository->findAll(BID);

        if (empty($Shortcuts)) {
            $this->Post->setValidator('shortcut', 'exists', false);
            AcmsLogger::info('ショートカットの表示順の更新に失敗しました', $this->Post->_aryV);
            return $this->Post;
        }
        if (count($Shortcuts) !== count($this->Post->getArray('sort')) ) {
            $this->Post->setValidator('shortcut', 'invalid', false);
            AcmsLogger::info('ショートカットの表示順の更新に失敗しました', $this->Post->_aryV);
            return $this->Post;
        }

        foreach ($Shortcuts as $i => $Shortcut) {
            $Shortcut->setSort(intval($this->Post->get('sort', null, $i)));
        }

        foreach ($Shortcuts as $Shortcut) {
            $this->ShortcutRepository->save($Shortcut);
        }

        $this->Post->set('sort#success', true);

        AcmsLogger::info('ショートカットの表示順を変更しました');

        return $this->Post;
    }

    /**
     * バリデート
     *
     * @param \Field_Validation
     **/
    public function validate(\Field_Validation $Post)
    {
        $Post->setMethod('sort', 'required', !empty($Post->getArray('sort')));
        $Post->validate(new ACMS_Validator());
    }
}
