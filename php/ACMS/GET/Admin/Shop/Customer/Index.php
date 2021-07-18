<?php

class ACMS_GET_Admin_Shop_Customer_Index extends ACMS_GET_Shop
{
    function get()
    {
        if ( !(IS_LICENSED and defined('LICENSE_PLUGIN_SHOP_PRO') and !!LICENSE_PLUGIN_SHOP_PRO) ) return '';
        if ( 'shop_customer_index' <> ADMIN ) return '';
        if ( !sessionWithAdministration() ) return '';

        $this->initVars();
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $limit  = LIMIT ? LIMIT : config('shop_customer_list_limit');
        $order  = ORDER ? ORDER : config('shop_customer_list_order');

        $delta  = config('admin_pager_delta');
        $attri  = config('admin_pager_attr');

        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('user');

        $SQL->addLeftJoin('shop_address', 'address_user_id', 'user_id');
/*
        $SQL->addLeftJoin('shop_receipt', 'receipt_user_id', 'address_user_id');
        $SQL->addSelect('receipt_datetime', 'user_lastest_datetime', null, 'max');
        $SQL->addGroup('receipt_user_id');
*/
        $SQL->addSelect('user_id');
        $SQL->addSelect('user_code');
        $SQL->addSelect('user_status');
        $SQL->addSelect('user_sort');
        $SQL->addSelect('user_name');
        $SQL->addSelect('user_mail');
        $SQL->addSelect('user_mail_mobile');
        $SQL->addSelect('user_url');
        $SQL->addSelect('user_generated_datetime');
        $SQL->addSelect('address_name');
        $SQL->addSelect('address_ruby');
        $SQL->addSelect('address_country');
        $SQL->addSelect('address_zip');
        $SQL->addSelect('address_prefecture');
        $SQL->addSelect('address_city');
        $SQL->addSelect('address_field_1');
        $SQL->addSelect('address_field_2');
        $SQL->addSelect('address_telephone');
        $SQL->addWhereOpr('address_primary', 'on');
        $SQL->addWhereOpr('user_auth', 'subscriber');
        $SQL->addWhereOpr('user_blog_id', BID);

        // receipt を加えると id.-*receipt になるため数が狂う
        $Pager  = SQL::newSelect('user');
        $Pager->addSelect('*', 'user_amount', null, 'count');
        $Pager->addLeftJoin('shop_address', 'address_user_id', 'user_id');
        $Pager->addWhereOpr('address_primary', 'on');
        $Pager->addWhereOpr('user_auth', 'subscriber');
        $Pager->addWhereOpr('user_blog_id', BID);

        if ( !!KEYWORD && !!ADMIN ) {
            $fds = array(
                'user_code',
                'user_name',
                'user_mail',
                'user_mail_mobile',
                'address_name',
                'address_ruby',
                'address_zip',
                'address_prefecture',
                'address_city',
                'address_field_1',
                'address_telephone',
            );
            $Pager->addWhereOpr('CONCAT('.implode(', ', $fds).')', '%'.KEYWORD.'%', 'LIKE');
            $SQL->addWhereOpr('CONCAT('.implode(', ', $fds).')', '%'.KEYWORD.'%', 'LIKE');
        }

        if ( !$pageAmount = intval($DB->query($Pager->get(dsn()), 'one')) ) {
            $Tpl->add('notFound');
            return $Tpl->get();
        }

        $vals = $this->buildPager(PAGE, $limit, $pageAmount, $delta, $attri, $Tpl, array(), array('admin' => ADMIN));

        $SQL->setLimit($limit, (PAGE - 1) * $limit);
        ACMS_Filter::userOrder($SQL, $order);

        if ( $all = $DB->query($SQL->get(dsn()), 'all') ) {

            foreach ( $all as $row ) {
                $Line = new Field();
                foreach ( $row as $key => $val ) {
                    $k = explode('_', $key);
                    if ( $k[0] == 'user' ) {
                        $Line->set(substr($key, strlen('user_')), $val);
                    }
                    if ( $k[0] == 'address' ) {
                        $Line->set($key, $val);
                    }
                }
                $vars = $this->buildField($Line, $Tpl, 'customer:loop');

                if ( !!ADMIN ) {
                    $SQL    = SQL::newSelect('shop_receipt');
                    $SQL->addSelect('receipt_datetime', 'lastest_datetime', null, 'max');
                    $SQL->addWhereOpr('receipt_user_id', $row['user_id']);
                    $last = $DB->query($SQL->get(dsn()),'row');

                    if ( !empty($last['lastest_datetime']) ) {
                        $vars['lastest_datetime']  = $last['lastest_datetime'];
                    } else {
                        $Tpl->add(array('notYet','customer:loop'));
                    }

                    $vars += array('editUrl' => acmsLink(array(
                        'bid'   => BID,
                        'admin' => 'shop_customer_edit',
                        'uid'   => $row['user_id'],
                    )), false);
                }
                $Tpl->add('customer:loop', $vars);
            }
        }

        $Tpl->add(null, $vals);

        return $Tpl->get();
    }
}
