<?php

class ACMS_GET_Admin_Shop_Receipt_Edit extends ACMS_GET_Admin_Edit
{
    function edit(& $Tpl)
    {
        $Receipt    =& $this->Post->getChild('receipt');
        $Address    =& $this->Post->getChild('address');
        $Primary    =& $this->Post->getChild('primary');

        if ( $Receipt->isNull() ) {
            if ( !empty($_GET['c']) ) {
                $Receipt->overload($this->loadReceipt($_GET['c']));
                $Address->overload(unserialize($Receipt->get('address')));
            } else {
                /*
                新規インサートはない
                $Receipt->set('key', 'val');
                */
            }
        }

        if ( $Receipt->get('user_id') != 0 ) {
            $Primary->overload($this->loadPrimary($Receipt->get('user_id')));
        } else {
            $Primary->overload($this->loadPrimaryFromLog());
            $Receipt->delete('user_id');
        }

        return true;
    }

    function loadReceipt($code)
    {
        $Receipt   = new Field_Validation();
        if ( !empty($code) ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('shop_receipt');
            $SQL->addWhereOpr('receipt_code', $code);
            if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
                foreach ( $row as $key => $val ) {
                    $Receipt->set(substr($key, strlen('receipt_')), $val);
                }
            }
        }
        return $Receipt;
    }

    function loadPrimary($uid)
    {
        $Address   = new Field_Validation();
        if ( !empty($uid) ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('shop_address');
            $SQL->addWhereOpr('address_user_id', $uid);
            $SQL->addWhereOpr('address_primary', 'on');
            if ( $row = $DB->query($SQL->get(dsn()), 'row') ) {
                foreach ( $row as $key => $val ) {
                    $Address->set($key, $val);
                }
            }
        }
        return $Address;
    }

    /**
     * UIDをもたない注文者の情報をDB的に記録していなかったので、ログから無理矢理取得
     *
     * @return Field
     */
    function loadPrimaryFromLog()
    {
        $code = $_GET['c'];
        $DB   = DB::singleton(dsn());
        $SQL  = SQL::newSelect('shop_receipt');
        $SQL->addSelect('receipt_log');
        $SQL->addWhereOpr('receipt_code', $code);
        $row  = $DB->query($SQL->get(dsn()), 'row');
        $Log  = unserialize(gzinflate($row['receipt_log']));
        $Primary = $Log->getChild('primary');
        $fds = $Primary->listFields();
        foreach ( $fds as $fd ) {
            $Primary->set('address_'.$fd, $Primary->get($fd));
        }
		// 2014/05/07 [CMS-1900] 受注詳細の配送先建物名が空の場合に、注文者の建物名がセットされる
		$sendto = $Log->get('sendto');
		if( $sendto == 'secondary' ){
			$Address = $Log->getChild('address');
			$fds = $Address->listFields();
			foreach ( $fds as $fd ) {
				$Primary->set($fd, $Address->get($fd));
			}
		}
        return $Primary;
    }
}