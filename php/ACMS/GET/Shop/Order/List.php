<?php

class ACMS_GET_Shop_Order_List extends ACMS_GET_Shop
{
    function get()
    {
        if ( !sessionWithSubscription() ) return '';

        $this->initVars();
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $delta  = config('shop_receipt_pager_delta');
        $attri  = config('shop_receipt_pager_attr');

        $this->receiptList($Tpl, $delta, $attri);

        return $Tpl->get();
    }

    function receiptList(& $Tpl, $delta, $attri)
    {
        if ( !$this->detectEdition() ) return $this->LICENSE_ERR_MSG;

        $limit   = LIMIT ? LIMIT : config('shop_receipt_list_limit');
        $order   = ORDER ? ORDER : config('shop_receipt_list_order');
        $status  = $this->Get->get('status');
        $deliver = $this->Get->get('deliver');
        $payment = $this->Get->get('payment');

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
        $SQL->addSelect('receipt_log');
        $SQL->addSelect('receipt_datetime');
        $SQL->addSelect('receipt_updated_datetime');
        $SQL->addSelect('receipt_user_id');
        $SQL->addWhereOpr('receipt_blog_id', BID);

        if ( !!ACMS_SID && !ADMIN ) $SQL->addWhereOpr('receipt_user_id', SUID);
        if ( !!UID && !!ADMIN ) $SQL->addWhereOpr('receipt_user_id', UID);
        if ( !!KEYWORD && !!ADMIN ) $SQL->addWhereOpr('receipt_code', '%'.KEYWORD.'%', 'LIKE');

        if ( !empty($status) && !!ADMIN  ) $SQL->addWhereOpr('receipt_status', $status);
        if ( !empty($deliver) && !!ADMIN ) $SQL->addWhereOpr('receipt_deliver', $deliver);
        if ( !empty($payment) && !!ADMIN ) $SQL->addWhereOpr('receipt_payment', $payment);

        $statusAry  = configArray('shop_receipt_status');
        $paymentAry = configArray('shop_order_payment_label');
        $deliverAry = configArray('shop_order_deliver_label');

        $Pager  = new SQL_Select($SQL);
        $Pager->setSelect('*', 'entry_amount', null, 'count');
        if ( !$pageAmount = intval($DB->query($Pager->get(dsn()), 'one')) ) {
            // Filter && ChangeButton Loop
            if ( !!ADMIN ) {
                $this->addLoop($Tpl, $statusAry, 'status', '#filter');
                $this->addLoop($Tpl, $paymentAry, 'payment');
                $this->addLoop($Tpl, $deliverAry, 'deliver');
            }

            $Tpl->add('notFound');
            return $Tpl->get();
        } else {
            // Filter && ChangeButton Loop
            if ( !!ADMIN ) {
                $this->addLoop($Tpl, $statusAry, 'status', '#filter');
                $this->addLoop($Tpl, $statusAry, 'status', '#button');
                $this->addLoop($Tpl, $paymentAry, 'payment');
                $this->addLoop($Tpl, $deliverAry, 'deliver');
            }
        }

        $vals = $this->buildPager(PAGE, $limit, $pageAmount, $delta, $attri, $Tpl, array(), array('admin' => ADMIN));

        $SQL->setLimit($limit, (PAGE - 1) * $limit);
        $this->receiptOrder($SQL, $order);

        if ( $all = $DB->query($SQL->get(dsn()), 'all') ) {
            foreach ( $all as $row ) {
                $Line = new Field();
                foreach ( $row as $key => $val ) {
                    $Line->set(substr($key, strlen('receipt_')), $val);
                }
                $vars = $this->buildField($Line, $Tpl, 'receipt:loop');

                $uid = $row['receipt_user_id'];
                if ( !empty($uid) ) {
                    $vars['user_mail'] = ACMS_RAM::userMail($uid);
                    $vars['user_name'] = ACMS_RAM::userName($uid);
                } else {
                    /**
                     * UIDをもたない注文者の情報をDB的に記録していなかったので、ログから無理矢理取得
                     * @var Field $Log
                     */
                    $Log = @unserialize(@gzinflate($row['receipt_log']));
                    if ( $Log ) {
                        $vars['user_mail'] = @$Log->get('mail');
                        $vars['user_name'] = @$Log->getChild('primary')->get('name');
                    }
                }
                if ( !!ADMIN ) {
                    $vars+= array('editUrl' => acmsLink(array(
                        'bid'   => BID,
                        'admin' => 'shop_receipt_edit',
                        'query' => array('c' => $vars['code'])
                    ), false));
                }

                $Tpl->add('receipt:loop', $vars);
            }
        }

        $Tpl->add(null, $vals);
    }

    function addLoop(& $Tpl, $array, $prefix, $surfix = null)
    {
        foreach ( $array as $code ) {
            $vars = array($prefix => $code);
            if ( $code == $this->Get->get($prefix) ) $vars += array(
                                                'selected' => config('attr_selected'),
                                                'checed'   => config('attr_checked'),);
            $Tpl->add(array($prefix.':touch#'.$code, $prefix.'loop'.$surfix));
            $Tpl->add($prefix.':loop'.$surfix, $vars);
        }
    }

    /**
     * move to ? ACMS_Filter::receiptOrder
     */
    function receiptOrder(& $SQL, $order)
    {
        $aryOrder   = explode('-', $order);
        $fd         = isset($aryOrder[0]) ? $aryOrder[0] : null;
        $seq        = isset($aryOrder[1]) ? $aryOrder[1] : null;

        if ( 'random' == $fd ) {
            $SQL->setOrder(SQL::newFunction(null, 'random'));
        } else {
            switch ( $fd ) {
                case 'code':
                    break;
                default:
                    $SQL->addOrder('receipt_'.$fd, $seq);
                    break;
            }
            $SQL->addOrder('receipt_code', $order);
        }

        return true;
    }

}