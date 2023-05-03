<?php

class ACMS_POST_Shop_Form_Submit extends ACMS_POST_Shop
{
    function post()
    {
        $this->initVars();
        $DB = DB::singleton(dsn());

        $Order = $this->extract('order', new ACMS_Validator());
		
        $TEMP    = $this->openCart();
		
		// 在庫数チェック
		$flg_valid = TRUE;
		foreach ( $TEMP as $key => $item ) {
			$entryField = loadEntryField( intval( $item['item_id'] ) );
			$int_stock = intval( $entryField->get($this->item_sku) );
			if( ! ( $int_stock >= intval( $item[$this->item_qty] ) ) ) {
				$flg_valid = FALSE;
				$TEMP[ $key ][$this->item_qty.':v#max'] = 'over';
			}
		}
		if( ! $flg_valid ){
			$this->Get->set('step', '');
			$this->screenTrans($this->orderTpl, $this->Get->get('step'));
			return $this->Post;
		}
		
        $SESSION =& $this->openSession();
		
        $PRIMARY = $SESSION->getChild('primary');
        $ADDRESS = $SESSION->getChild('address');
        /**
         * start save to FIELD Object
         */
        $FIELD =& $this->Post->getChild('field');

        /**
         * store: session
         */
        $list = $SESSION->listFields();
        foreach ( $list as $key ) {
            $FIELD->set($key, $SESSION->get($key));
        }

        /**
         * validation
         */
        if ( 0
            or empty($TEMP)
            or empty($ADDRESS)
            or !$SESSION->get('total')
            or !$SESSION->get('subtotal')
        ) {
            $TEMP = array();
            $this->closeSession($SESSION);
            $this->closeCart($TEMP);

            $this->Post->set('step', 'error');
            return $this->Post;
        }


        /**
         * store: order note
         */
        $FIELD->set('note', $Order->get('note'));

        /**
         * store: address
         */
        $fds = $PRIMARY->listFields();
        foreach ( $fds as $fd ) {
            $FIELD->set($fd.'#1', $PRIMARY->get($fd));
        }
        $fds = $ADDRESS->listFields();
        foreach ( $fds as $fd ) {
            $FIELD->set($fd.'#2', $ADDRESS->get($fd));
        }

        // すでに送信していれば2重で記録等をしない
        if ( !$this->alreadySubmit() ) {
            /**
             * build: item:loop
             */
            $cartTpl    = findTemplate($this->cartTpl);
            $Tpl        = new Template(setGlobalVars(Storage::get($cartTpl)), new ACMS_Corrector());
            foreach ( $TEMP as $item ) {
                if ( config('shop_tax_calculate') != 'extax' ) {
                    $item[$this->item_price] += $item[$this->item_price.'#tax'];
                }
                $Tpl->add('item:loop', $item);
            }
            $Tpl->add(null);
            $cartBody   = $Tpl->get();

            /**
             * resultステップ用に，現在のカートの内容をポートレートに退避しておく
             * @todo issue: array_valuesで添え字をなくさないと、Fieldクラスが暴走する / v150時点で応急処置
             */
            $SESSION->set('portrait_cart', array_values($TEMP));

            /**
             * initVars
             */
            $status     = configArray('shop_receipt_status');
            $receiptNum =  $this->getReceiptNum();
            $SESSION->set('status', $status[0]);
            $SESSION->set('code', $receiptNum);
            $suid = ( !!SUID ) ? SUID : 0;

            /**
             * メール用に変数セット
             */
            $FIELD->set('cart', $cartBody);
            $FIELD->set('code', $receiptNum);

            /**
             * ##### insert `shop_receipt` table #####
             */
            $SQL = SQL::newInsert('shop_receipt');
            $SQL->addInsert('receipt_code', $SESSION->get('code'));
            $SQL->addInsert('receipt_status', $SESSION->get('status'));
            $SQL->addInsert('receipt_total', $SESSION->get('total'));
            $SQL->addInsert('receipt_subtotal', $SESSION->get('subtotal'));
            $SQL->addInsert('receipt_payment', $SESSION->get('payment'));
            $SQL->addInsert('receipt_deliver', $SESSION->get('deliver'));
            $SQL->addInsert('receipt_address', serialize($ADDRESS));
            $SQL->addInsert('receipt_request_date', $SESSION->get('request_date'));
            $SQL->addInsert('receipt_request_time', $SESSION->get('request_time'));
            $SQL->addInsert('receipt_request_others', $SESSION->get('request_others'));
            $SQL->addInsert('receipt_charge_payment', $SESSION->get('charge#payment'));
            $SQL->addInsert('receipt_charge_deliver', $SESSION->get('charge#deliver'));
            $SQL->addInsert('receipt_charge_others', $SESSION->get('charge#others'));
            $SQL->addInsert('receipt_note', $Order->get('note'));
            $SQL->addInsert('receipt_log', gzdeflate(serialize($SESSION)));
            $SQL->addInsert('receipt_datetime', date('Y-m-d H:i:s'));
            $SQL->addInsert('receipt_updated_datetime', date('Y-m-d H:i:s'));
            $SQL->addInsert('receipt_user_id', $suid);
            $SQL->addInsert('receipt_blog_id', BID);
            $DB->query($SQL->get(dsn()), 'exec');
    
            /**
             * ##### insert `shop_detail_receipt` table #####
             */
            foreach ( $TEMP as $item ) {
                /**
                 * Update Item Stock Keeping Unit
                 */
                $sku = $this->getSku($item[$this->item_id]);
                $sku = ($sku <= $item[$this->item_qty]) ? '' : $sku - $item[$this->item_qty];
				
				$sku = (intval($sku) < 0) ?0:intval($sku);
				
                $SQL    = SQL::newUpdate('field');
                $SQL->addUpdate('field_value', $sku);
                $SQL->addWhereOpr('field_key', $this->item_sku);
                $SQL->addWhereOpr('field_eid',$item[$this->item_id]);
                $DB->query($SQL->get(dsn()), 'exec');
                unset($sku);

                /**
                 * Insert Receipt_Detail_Row
                 */
                $did = $DB->query(SQL::nextval('shop_receipt_detail_id', dsn()), 'seq');
                $SQL = SQL::newInsert('shop_receipt_detail');
                $SQL->addInsert('receipt_detail_id', $did);
                $SQL->addInsert('receipt_detail_name', $item[$this->item_name]);
                $SQL->addInsert('receipt_detail_price', ($item[$this->item_price] + $item[$this->item_price.'#tax']));
                $SQL->addInsert('receipt_detail_qty', $item[$this->item_qty]);
                $SQL->addInsert('receipt_detail_item_id', $item[$this->item_id]);
                unset(  $item[$this->item_name],
                        $item[$this->item_price],
                        $item[$this->item_qty],
                        $item[$this->item_id]   );
                $SQL->addInsert('receipt_detail_field', serialize($item));
                $SQL->addInsert('receipt_detail_datetime', date('Y-m-d H:i:s'));
                $SQL->addInsert('receipt_detail_parent_code', $SESSION->get('code'));
                $SQL->addInsert('receipt_detail_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
				
            }

            /**
             * Form_Submitをインスタンス化して実行する
             */
            $Submit = new ACMS_POST_Form_Submit();
            $Submit->_CSRF  = false;

            $Submit->Q      = $this->Q;
            $Submit->Get    = $this->Get;
            $Submit->Post   = $this->Post;
            $Submit->post();

        } else {
            $SESSION->set('submitted', true);
        }

        /**
         * セッションとカートをクリアする
         */
        $TEMP = array();
        $this->closeSession($SESSION);
        $this->closeCart($TEMP);

        $this->Post->set('code', $SESSION->get('code'));
        $this->Post->set('step', 'result');
        return $this->Post;
    }

    function makeReceiptNum()
    {
        $start  = date('Y-m-d 00:00:00');
        $end    = date('Y-m-d 00:00:00', strtotime('+1 day'));
        
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('shop_receipt');
        $SQL->addWhereBw('receipt_datetime', $start, $end, 'AND', null);
        $res = $DB->query($SQL->get(dsn()), 'one');

        if ( !empty($res) ) {
            $SQL = SQL::newSelect('shop_receipt');
            $SQL->addSelect(SQL::newFunction('receipt_datetime', array('SUBSTR', 0, 10)), 'receipt_date');
            $SQL->addSelect(SQL::newFunction('receipt_code', array('SUBSTR', 9, 5)), 'preNum', null, 'max');
            $SQL->addWhereBw('receipt_datetime', $start, $end, 'AND', null);
            $SQL->addGroup('receipt_date');
            $SQL->addOrder('receipt_date', 'DESC');
            $num = $DB->query($SQL->get(dsn()), 'row');
            $num = $num['preNum'] + 1;
        } else {
            $num = '00001';
        }
        return date('YmdHis').sprintf("-%03d", mt_rand(0, 999));
    }

    function getReceiptNum()
    {
        $DB = DB::singleton(dsn());
        do {
            $unique = $this->makeReceiptNum();
            $SQL = SQL::newSelect('shop_receipt');
            $SQL->addWhereOpr('receipt_code', $unique);
            $chk = $DB->query($SQL->get(dsn()), 'row');
        } while ( !empty($chk) );

        return $unique;
    }

    function getSku($eid)
    {
        $DB = DB::singleton(dsn());
        $SQL    = SQL::newSelect('field');
        $SQL->addSelect('field_value');
        $SQL->addWhereOpr('field_key', $this->item_sku);
        $SQL->addWhereOpr('field_eid', $eid);
        return intval($DB->query($SQL->get(dsn()), 'one'));
    }
}