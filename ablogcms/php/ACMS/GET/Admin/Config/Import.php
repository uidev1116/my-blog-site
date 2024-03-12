<?php

class ACMS_GET_Admin_Config_Import extends ACMS_GET_Admin_Config
{
    function extendTemplate(&$vars, &$Tpl)
    {
        if ($notice = $this->Post->getArray('notice')) {
            foreach ($notice as $message) {
                if (isset($message['unlink']) && is_array($message['unlink'])) {
                    foreach ($message['unlink'] as $unlink) {
                        $Tpl->add('unlink:loop', $unlink);
                    }
                }
                $Tpl->add('notice:loop', $message);
            }
        }

        if ($this->Post->get('import') === 'success') {
            $Tpl->add('success');
        }
    }
}
