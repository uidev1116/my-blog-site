<?php

class ACMS_GET_Member_ResetPassword extends ACMS_GET_Member
{
    /**
     * テンプレート組み立て
     *
     * @param Template $tpl
     * @return void
     */
    protected function buildTpl(Template $tpl): void
    {
        $vars = [];
        if ($this->Post->isNull()) {
            $tpl->add('sendMsg#before');
            $tpl->add('form');
        } else {
            if ($this->Post->isValidAll()) {
                $tpl->add('sendMsg#after');
            } else {
                $vars += $this->buildField($this->Post, $tpl);
                $tpl->add('form', $vars);
            }
        }
        $tpl->add(null, $vars);
    }
}
