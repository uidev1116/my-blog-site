<?php

class ACMS_GET_Form extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $this->step = $this->Post->get('error');
        if (empty($this->step)) {
            $this->step = $this->Get->get('step');
        }
        if ($this->Post->isValidAll()) {
            $this->step = $this->Post->get('step', $this->step);
        } else {
            $this->error($Tpl);
        }
        if (!ACMS_POST) {
            $this->step = 'step';
        }
        $this->build_tpl($Tpl);
        $tpl = $Tpl->get();

        return $tpl;
    }

    /**
     * テンプレートの組み立て
     *
     * @param Template & $Tpl
     */
    function build_tpl(&$Tpl)
    {
        $block  = !(empty($this->step) or is_bool($this->step)) ? 'step#' . $this->step : 'step';
        $pattern = '/<!--[\t 　]*BEGIN[\s]+' . preg_quote($block, '/') . '[\t 　]*-->/u';
        if (!multiBytePregMatch($pattern, $this->tpl)) {
            $block = 'step';
        }
        $this->Post->delete('step');
        if (defined('FORM_ENTRY_ID') && !!FORM_ENTRY_ID) {
            $entry  = ACMS_RAM::entry(FORM_ENTRY_ID);
            $fmid   = $entry['entry_form_id'];

            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('form');
            $SQL->addSelect('form_code');
            $SQL->addWhereOpr('form_id', $fmid);
            $Where  = SQL::newWhere();
            $Where->addWhereOpr('form_blog_id', BID, '=', 'OR');
            $Where->addWhereOpr('form_scope', 'global', '=', 'OR');
            $SQL->addWhere($Where);
            $fcode  = $DB->query($SQL->get(dsn()), 'one');

            $this->Post->add('form_id', $fcode);
        }
        $Tpl->add($block, $this->buildField($this->Post, $Tpl, $block, ''));
    }

    /**
     * エラー処理
     *
     * @param Template & $Tpl
     */
    function error(&$Tpl)
    {
        $Errors = array();
        if (isset($this->Post->_aryChild['field'])) {
            $Field  = $this->Post->_aryChild['field'];
            foreach ($Field->_aryV as $key => $val) {
                foreach ($val as $valid) {
                    if (
                        1
                        and isset($valid[0])
                        and $valid[0] === false
                    ) {
                        $Errors[]   = $key;
                    }
                }
            }
        }
        if (!empty($Errors)) {
            $Tpl->add('error', array(
                'formID'    => $this->Post->get('id'),
                'errorKey'  => implode(',', $Errors),
            ));
        }
    }
}
