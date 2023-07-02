<?php

class ACMS_GET_Shop_Order_Detail extends ACMS_GET_Shop
{
    function get()
    {
        if ( !sessionWithSubscription() ) return '';

        $this->initVars();
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $this->receiptDetail($Tpl);

        return $Tpl->get();
    }

    function receiptDetail(& $Tpl)
    {
        if ( !$this->detectEdition() ) return $this->LICENSE_ERR_MSG;

        if ( !$this->Get->isExists('c') ) return '';

        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('shop_receipt');
        $SQL->addSelect('receipt_code');
        $SQL->addSelect('receipt_status');
        $SQL->addSelect('receipt_payment');
        $SQL->addSelect('receipt_deliver');
        $SQL->addSelect('receipt_address');
        $SQL->addSelect('receipt_total');
        $SQL->addSelect('receipt_subtotal');
        $SQL->addSelect('receipt_request_date');
        $SQL->addSelect('receipt_request_time');
        $SQL->addSelect('receipt_request_others');
        $SQL->addSelect('receipt_charge_payment');
        $SQL->addSelect('receipt_charge_deliver');
        $SQL->addSelect('receipt_charge_others');
        $SQL->addSelect('receipt_note');
        $SQL->addWhereOpr('receipt_blog_id', BID);
        $SQL->addWhereOpr('receipt_code', $this->Get->get('c'));

        if ( !ADMIN ) {
            $SQL->addWhereOpr('receipt_user_id', SUID);
        }

        if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
            $Field = new Field();
            foreach ( $row as $key => $val ) {
                if ( $key == 'receipt_address' ) {
                    $Address = unserialize($val);
                    continue;
                }
                $Field->set(substr($key, strlen('receipt_')), $val);
            }
            $rootVars = $this->buildField($Field, $Tpl, null);
            $addressVars = $this->buildField($Address, $Tpl, 'address');

            /*
            * load receipt detail
            */
            $SQL = SQL::newSelect('shop_receipt_detail');
            $SQL->addSelect('receipt_detail_name');
            $SQL->addSelect('receipt_detail_price');
            $SQL->addSelect('receipt_detail_qty');
            $SQL->addSelect('receipt_detail_field');
            $SQL->addSelect('receipt_detail_item_id');
            $SQL->addWhereOpr('receipt_detail_parent_code', $_GET['c']);
            $all = $DB->query($SQL->get(dsn()), 'all');

            foreach ( $all as $row ) {
                $Line = new Field();
                foreach ( $row as $key => $val ) {
                    if ( $key == 'receipt_detail_field' ) continue;
                    $Line->set(substr($key, strlen('receipt_detail_')), $val);
                }
                $eid = $row['receipt_detail_item_id'];

                @$vars = $this->buildField($Line, $Tpl, 'item:loop');
                @$vars['sum'] = $vars['price'] * $vars['qty'];
                @$vars += unserialize($row['receipt_detail_field']);
                @$vars += array('url' => acmsLink(array(
                    'admin' => false,
                    'eid'   => $eid,
                    'bid'   => ACMS_RAM::entryBlog($row['receipt_detail_item_id']),
                )));
                $Tpl->add('item:loop', $vars);
                unset($vars);
            }

        $Tpl->add('address', $addressVars);
        $Tpl->add(null, $rootVars);

        }
    }
}