<?php

class ACMS_GET_Admin_Dashboard_LicenseStatus extends ACMS_GET
{
    function get()
    {
        if ( !sessionWithAdministration() ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('blog');
        $SQL->addSelect('blog_generated_datetime');
        $SQL->addWhereOpr('blog_id', 1);
        $q = $SQL->get(dsn());
        $row = $DB->query($q, 'row');

        switch ( LICENSE_EDITION ) {
            case 'enterprise':
                $vars['edition'] = 'Enterprise';
                break;
            case 'professional':
                $vars['edition'] = 'Professional';
                break;
            default:
                $vars['edition'] = 'Standard';
                break;
        }

        if ( DOMAIN != 'localhost' && !is_private_ip(DOMAIN) ) {

            // またはブログリミットがUNLIMITED_NUMBER_OF_USERSであった場合
            if ( LICENSE_BLOG_LIMIT == UNLIMITED_NUMBER_OF_USERS ) {
                $block['unlimited'] = true;
            } else {
                $SQL    = SQL::newSelect('user');
                $SQL->addSelect('*', 'amount', null, 'count');
                $SQL->addWhereOpr('user_auth', 'subscriber', '!=');
                $amount = $DB->query($SQL->get(dsn()), 'one');
                $vars['amount']     = $amount;
                $vars['max']    = LICENSE_BLOG_LIMIT;
            }
            
            if ( empty($vars['term']) ) unset($vars['term']);

        } else {
            if ( LICENSE_BLOG_LIMIT == UNLIMITED_NUMBER_OF_USERS ) {
                $block['unlimited'] = true;
            } else {
                $SQL    = SQL::newSelect('user');
                $SQL->addSelect('*', 'amount', null, 'count');
                $SQL->addWhereOpr('user_auth', 'subscriber', '!=');
                $amount = $DB->query($SQL->get(dsn()), 'one');
                $vars['amount']     = $amount;
                $vars['max']    = LICENSE_BLOG_LIMIT;
            }
        }
        
        $options = array(
                        'LICENSE_OPTION_SUBDOMAIN',
                        'LICENSE_OPTION_OWNDOMAIN',
                        'LICENSE_OPTION_OEM',
                        'LICENSE_PLUGIN_SHOP_PRO',
                        'LICENSE_PLUGIN_MAILMAGAZINE',
                        );
        
        foreach ( $options as $opt ) {
            if ( !defined($opt) ) define($opt, null);
        }
        
        $block['domain']    = LICENSE_OPTION_SUBDOMAIN;
        $block['domain2']   = LICENSE_OPTION_OWNDOMAIN;
        $block['oem']       = LICENSE_OPTION_OEM;
        $block['shop']      = LICENSE_PLUGIN_SHOP_PRO;
        $block['magazine']  = LICENSE_PLUGIN_MAILMAGAZINE;

        if ( defined('LICENSE_OPTION_PLUSDOMAIN') && intval(LICENSE_OPTION_PLUSDOMAIN) > 0 ) {
            $Tpl->add('domain3', array(
                'plus'  => LICENSE_OPTION_PLUSDOMAIN,
            ));
        }

        foreach ( $block as $key => $val ) {
            if( !empty($val) ) $Tpl->add($key);
        }
        
        $Tpl->add(null, $vars);
        return $Tpl->get();
    }
}
