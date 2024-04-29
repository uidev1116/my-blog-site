<?php

class ACMS_POST_Blog_MaintenanceMode extends ACMS_POST_Blog
{
    function post()
    {
        $blog = $this->extract('blog');
        $blog->setMethod('maintenance_mode', 'required');
        $blog->setMethod('maintenance_mode', 'in', ['on', 'off']);
        $blog->setMethod('blog', 'operable', sessionWithAdministration());
        $blog->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            if ($blog->get('maintenance_mode') === 'on') {
                $mode = $blog->get('maintenance_http_status');
            } else {
                $mode = '';
            }
            $sql = SQL::newUpdate('blog');
            $sql->setUpdate('blog_maintenance_mode', $mode);
            $sql->addWhereOpr('blog_id', BID);
            DB::query($sql->get(dsn()), 'exec');
            ACMS_RAM::blog(BID, null);
            ACMS_RAM::setBlogMaintenanceMode(BID, $mode);

            AcmsLogger::info('「' . ACMS_RAM::blogName(BID) . '」ブログをメンテナンスモードに変更しました');
        } else {
            AcmsLogger::info('「' . ACMS_RAM::blogName(BID) . '」ブログのメンテナンスモードへの変更に失敗しました');
        }
        return $this->Post;
    }
}
