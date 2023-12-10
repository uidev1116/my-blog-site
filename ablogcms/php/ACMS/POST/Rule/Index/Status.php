<?php

class ACMS_POST_Rule_Index_Status extends ACMS_POST
{
    function post()
    {
        try {
            if (!sessionWithAdministration()) {
                throw new \RuntimeException('権限がありません');
            }
            if (!(($status = $this->Post->get('status')) && in_array($status, array('open', 'close')))) {
                throw new \RuntimeException('指定されたステータスが不正です');
            }
            if (empty($_POST['checks']) || !is_array($_POST['checks'])) {

            }
            $targetRules = [];
            foreach ($_POST['checks'] as $rid) {
                if (!$rid = idval($rid)) continue;
                $SQL = SQL::newUpdate('rule');
                $SQL->addUpdate('rule_status', $status);
                $SQL->addWhereOpr('rule_id', $rid);
                $SQL->addWhereOpr('rule_blog_id', BID);
                DB::query($SQL->get(dsn()), 'exec');

                ACMS_RAM::rule($rid, null);

                $rule = loadRule($rid);
                $targetRules[] = $rule->get('name');
            }
            AcmsLogger::info('指定されたルールのスタースを「' . $status . '」に変更しました', [
                'targetRules' => $targetRules,
            ]);
        } catch (\Exception $e) {
            AcmsLogger::info('指定されたルールのステータス変更に失敗しました', [
                'message' => $e->getMessage(),
            ]);
        }
        return $this->Post;
    }
}
