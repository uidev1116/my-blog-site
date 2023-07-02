<?php

class ACMS_POST_Module_Update extends ACMS_POST_Module
{
    /**
     * CSRF対策
     *
     * @var bool
     */
    protected $isCSRF = true;

    /**
     * 二重送信対策
     *
     * @var bool
     */
    protected $checkDoubleSubmit = true;

    /**
     * @return false|Field
     */
    function post()
    {
        if ( !$rid = idval($this->Post->get('rid')) ) $rid = null;
        if ( !$mid = idval($this->Post->get('mid')) ) $mid = null;

        //---------
        // module
        $this->Post->set('module', array(
            'name', 'status', 'identifier', 'label', 'description', 'cache', 'scope', 'custom_field', 'layout_use', 'api_use',
            'bid', 'uid', 'cid', 'eid','keyword', 'tag', 'field_',
            'start_date', 'start_time', 'end_date', 'end_time',
            'page', 'order',
            'uid_scope', 'cid_scope', 'eid_scope', 'keyword_scope', 'tag_scope', 'field_scope',
            'start_scope', 'end_scope', 'page_scope', 'order_scope',
            'bid_axis', 'cid_axis',
        ));
        $Module = $this->extract('module');
        if ( 'global' !== $Module->get('scope') ) {
            $Module->set('scope', 'local');
        }

        $Module->setMethod('name', 'required');
        $Module->setMethod('module', 'midIsNull', $mid);
        $Module->setMethod('module', 'invalidLicense', IS_LICENSED);
        $Module->setMethod('identifier', 'double', array($Module->get('scope'), $mid));
        $Module->setMethod('label', 'required');

        $allow_multiple_args = configArray('module_allow_multiple_arguments');
        if ( empty($allow_multiple_args) ) {
            $allow_multiple_args = array('Entry_Body', 'Entry_Summary', 'Entry_List', 'Entry_Headline', 'Entry_Photo', 'Entry_TagRelational');
        }
        if ( !in_array($Module->get('name'), $allow_multiple_args) ) {
            $Module->setMethod('bid', 'intOrGlobalVars');
            $Module->setMethod('uid', 'intOrGlobalVars');
            $Module->setMethod('cid', 'intOrGlobalVars');
            $Module->setMethod('eid', 'intOrGlobalVars');
        }
        $Module->setMethod('page', 'intOrGlobalVars');

        if ( roleAvailableUser() ) {
            $Module->setMethod('module', 'operative', roleAuthorization('module_edit', BID) ?
                true : Auth::checkShortcut('Module_Update', ADMIN, 'mid', $mid)
            );
        } else {
            $Module->setMethod('module', 'operative', sessionWithAdministration() ?
                true : Auth::checkShortcut('Module_Update', ADMIN, 'mid', $mid)
            );
        }

        $Module->validate(new ACMS_Validator_Module());
        $this->fix($Module);

        //-----------
        // config
        $Config = $this->extract('config');
        $Config = Config::setValide($Config, $rid, $mid);
        $Config->validate(new ACMS_Validator());
        $Config = Config::fix($Config);

        //-----------
        // field
        $Field = $this->extract('field', new ACMS_Validator());

        if ( $this->Post->isValidAll() ) {
            //---------
            // module
            $start  = $Module->get('start_date') ? ($Module->get('start_date').' '.$Module->get('start_time')) : null;
            $end    = $Module->get('end_date') ? ($Module->get('end_date').' '.$Module->get('end_time')) : null;

            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newUpdate('module');
            $SQL->addUpdate('module_name', $Module->get('name'));
            $SQL->addUpdate('module_identifier', strval($Module->get('identifier')));
            $SQL->addUpdate('module_label', $Module->get('label'));
            $SQL->addUpdate('module_description', strval($Module->get('description')));
            $SQL->addUpdate('module_status', $Module->get('status', 'open'));
            $SQL->addUpdate('module_scope', $Module->get('scope'));
            $SQL->addUpdate('module_cache', intval($Module->get('cache', 0)));
            $SQL->addUpdate('module_bid', $Module->get('bid'));
            $SQL->addUpdate('module_uid', $Module->get('uid'));
            $SQL->addUpdate('module_cid', $Module->get('cid'));
            $SQL->addUpdate('module_eid', $Module->get('eid'));
            $SQL->addUpdate('module_keyword', $Module->get('keyword'));
            $SQL->addUpdate('module_tag', $Module->get('tag'));
            $SQL->addupdate('module_field', $Module->get('field_'));
            $SQL->addUpdate('module_start', $start);
            $SQL->addUpdate('module_end', $end);
            $SQL->addUpdate('module_page', $Module->get('page'));
            $SQL->addUpdate('module_order', $Module->get('order'));
            $SQL->addUpdate('module_uid_scope', $Module->get('uid_scope'));
            $SQL->addUpdate('module_cid_scope', $Module->get('cid_scope'));
            $SQL->addUpdate('module_eid_scope', $Module->get('eid_scope'));
            $SQL->addUpdate('module_keyword_scope', $Module->get('keyword_scope'));
            $SQL->addUpdate('module_tag_scope', $Module->get('tag_scope'));
            $SQL->addupdate('module_field_scope', $Module->get('field_scope'));
            $SQL->addUpdate('module_start_scope', $Module->get('start_scope'));
            $SQL->addUpdate('module_end_scope', $Module->get('end_scope'));
            $SQL->addUpdate('module_page_scope', $Module->get('page_scope'));
            $SQL->addUpdate('module_order_scope', $Module->get('order_scope'));
            $SQL->addUpdate('module_bid_axis', $Module->get('bid_axis'));
            $SQL->addUpdate('module_cid_axis', $Module->get('cid_axis'));
            $SQL->addUpdate('module_custom_field', $Module->get('custom_field', 1));
            $SQL->addUpdate('module_layout_use', $Module->get('layout_use', 1));
            $SQL->addUpdate('module_api_use', $Module->get('api_use', 'off'));

            $SQL->addWhereOpr('module_id', $mid);
            $SQL->addWhereOpr('module_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');

            //--------
            // config
            Config::saveConfig($Config, BID, $rid, $mid);

            //-------
            // field
            Common::saveField('mid', $mid, $Field);

            //-------------
            // delete cache
            $cache = Cache::module();
            $cache->forget(md5($Module->get('name') . $Module->get('identifier')));

            $this->Post->set('edit', 'update');
        } else {
            $this->Post->set('validate', true);
        }

        return $this->Post;
    }
}
