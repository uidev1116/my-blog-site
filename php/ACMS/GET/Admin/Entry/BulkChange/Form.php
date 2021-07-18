<?php

class ACMS_GET_Admin_Entry_BulkChange_Form extends ACMS_GET_Admin_Entry_BulkChange
{
    public function get()
    {
        $tpl = new Template($this->tpl, new ACMS_Corrector());

        try {
            $step = $this->Post->get('step');
            $error = $this->Post->get('error');
            $block = !(empty($step) or is_bool($step)) ? 'step#' . $step : 'step#1';
            $this->Post->delete('step');
            $this->Post->delete('error');
            if ($error) {
                $tpl->add('error:' . $error);
            }
            $tpl->add($block, $this->buildField($this->Post, $tpl, $block, ''));
        } catch (\Exception $e) {

        }
        return $tpl->get();
    }
}
