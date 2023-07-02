<?php

class ACMS_POST_Module_Index_Blog extends ACMS_POST_Module
{
    function post()
    {
        if ( !($bid = intval($this->Post->get('bid'))) ) $bid = null;
        $this->Post->setMethod('checks', 'required');
        if ( enableApproval($bid, null) ) {
            $this->Post->setMethod('module', 'operable', sessionWithApprovalAdministrator($bid, null));
        } else if ( roleAvailableUser() ) {
            $this->Post->setMethod('module', 'operable', roleAuthorization('admin_etc', $bid));
        } else {
            $this->Post->setMethod('module', 'operable', sessionWithAdministration($bid));
        }

        $this->Post->validate(new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            @set_time_limit(0);
            $DB     = DB::singleton(dsn());

            $error  = array();
            foreach ( array_reverse($this->Post->getArray('checks')) as $mid ) {
                $id     = preg_split('@:@', $mid, 2, PREG_SPLIT_NO_EMPTY);
                $mid    = $id[1];
                if ( !($mid = intval($mid)) ) continue;

                $Module     = loadModule($mid);
                $identifier = $Module->get('identifier');
                $scope      = $Module->get('scope');

                if ( Module::double($identifier, $mid, $scope, $bid) ) {
                    //--------
                    // module
                    $SQL    = SQL::newUpdate('module');
                    $SQL->addUpdate('module_blog_id', $bid);
                    $SQL->addWhereOpr('module_id', $mid);
                    $DB->query($SQL->get(dsn()), 'exec');

                    //--------
                    // config
                    $SQL    = SQL::newUpdate('config');
                    $SQL->addUpdate('config_blog_id', $bid);
                    $SQL->addWhereOpr('config_module_id', $mid);
                    $DB->query($SQL->get(dsn()), 'exec');
                    Config::forgetCache(BID, null, $mid);

                    //-------
                    // field
                    $SQL    = SQL::newUpdate('field');
                    $SQL->addUpdate('field_blog_id', $bid);
                    $SQL->addWhereOpr('field_mid', $mid);
                    $DB->query($SQL->get(dsn()), 'exec');
                    Common::deleteFieldCache('mid', $mid);

                } else {
                    $error[] = $mid;
                }
            }

            if ( empty($error) ) {
                $this->Post->set('refreshed', 'refreshed');
            } else {
                $this->Post->set('error', 'blog');
            }
        }

        return $this->Post;
    }
}
