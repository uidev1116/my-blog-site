<?php

class ACMS_GET_Admin_ActionMenu extends ACMS_GET
{
    function get()
    {
        if ( 0
            || !$this->checkPermission()
            || LAYOUT_PREVIEW
            || Preview::isPreviewMode()
        ) {
            return '';
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = array();

        if (IS_DEVELOPMENT && defined('UNLICENSED_REASON')) {
            $Tpl->add('status#' . UNLICENSED_REASON);
        }

        $vars += array(
            'name' => ACMS_RAM::userName(SUID),
            'icon' => loadUserIcon(SUID),
            'logout' => acmsLink(array('_inherit' => true)),
        );

        if ( sessionWithContribution() ) {
            if ( IS_LICENSED ) {
                $Tpl->add('insert', array('cid' => CID));
                foreach ( configArray('ping_weblog_updates_endpoint') as $val ) {
                    $Tpl->add('ping_weblog_updates_endpoint:loop', array(
                        'ping_weblog_updates_endpoint'  => $val,
                    ));
                }
                foreach ( configArray('ping_weblog_updates_extended_endpoint') as $val ) {
                    $Tpl->add('ping_weblog_updates_extended_endpoint:loop', array(
                        'ping_weblog_updates_extended_endpoint' => $val,
                    ));
                }
            }
        }

        //-------
        // admin
        $Tpl->add('admin');

        //---------------------
        // approval infomation
        if ( approvalAvailableUser() ) {
            if ( $amount = Approval::notificationCount() ) {
                $Tpl->add('approval', array(
                    'badge' => $amount,
                    'url'   => acmsLink(array(
                        'bid'   => BID,
                        'admin' => 'approval_notification',
                    )),
                ));
            }
        }

        $Tpl->add(null, $vars);
        return $Tpl->get();
    }

    /**
     * @return bool
     */
    protected function checkPermission()
    {
        if (timemachineMode()) {
            return false;
        }

        if ( 1
            and \ACMS_RAM::userGlobalAuth(SUID) !== 'on'
            and SBID !== BID
        ) {
            return false;
        }

        if ( !(1
            and \ACMS_RAM::blogLeft(SBID) <= \ACMS_RAM::blogLeft(BID)
            and \ACMS_RAM::blogRight(SBID) >= \ACMS_RAM::blogRight(BID)
        ) ) {
            return false;
        }

        switch ( \ACMS_RAM::userAuth(SUID) ) {
            case 'administrator':
            case 'editor':
            case 'contributor':
            case 'subscriber':
                break;
            default:
                return false;
        }
        return true;
    }
}
