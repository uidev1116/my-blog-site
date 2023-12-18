<?php

class ACMS_GET_Admin_Entry_BulkChange_Field extends ACMS_GET_Admin_Entry_BulkChange
{
    public function get()
    {
        $tpl = new Template($this->tpl, new ACMS_Corrector());
        try {
            $this->buildFieldBlock($tpl);
        } catch (\Exception $e) {
            AcmsLogger::debug($e->getMessage(), Common::exceptionArray($e));
        }
        return $tpl->get();
    }

    protected function buildFieldBlock($tpl)
    {
        foreach (configArray('bulkChangeFieldKey') as $i => $key) {
            $block = 'field#' . $key;
            $tpl->add(array($block, 'changeField:loop'));
            $tpl->add('changeField:loop', array(
                'label' => config('bulkChangeFieldLabel', 'ラベルを設定してください', $i),
                'key' => $key,
            ));
        }
    }
}
