<?php

class ACMS_GET_Shop_Cart_List extends ACMS_GET_Shop
{
    function initPrivateVars()
    {
        $this->imageX           = intval(config('shop_cart_list_image_x'));
        $this->imageY           = intval(config('shop_cart_list_image_y'));
        $this->imageTrim        = config('shop_cart_list_image_trim');
        $this->imageZoom        = config('shop_cart_list_image_zoom');
        $this->imageCenter      = config('shop_cart_list_image_center');
    }

    function get()
    {	
        $this->initVars();

        $this->initPrivateVars();

        $TEMP = $this->openCart();
		
        $Tpl = $this->buildList($TEMP);

        $this->closeCart($TEMP);

        return $Tpl;
    }

    function buildList(& $TEMP)
    {
        $step   = $this->Post->get('step', null);
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);
        $amount = array();

        // カートが空のときのパターン
        if ( empty($TEMP) ) {
            $Tpl->add('notFound');
            return $Tpl->get();
        }

        // 商品を削除するときのパターン
        if ( $this->Get->isExists('delete') ) {
            $delete_target = $this->Get->get('delete');
            $_SESSION['deleted'] = $TEMP[$delete_target];
            unset($TEMP[$delete_target]);
            $this->closeCart($TEMP);

            $loc = preg_replace('@\?\S*@','',REQUEST_URL);
            redirect($loc);
        }

        // 通常の表示をするパターン
        foreach ( $TEMP as $hash => $row ) {
            $price  = $row[$this->item_price];
            $qty    = $row[$this->item_qty];
			
            $tax    = $row[$this->item_price.'#tax'];
            $sum    = $row[$this->item_price.'#sum'];

			$entryField = loadEntryField( intval( $row[$this->item_id] ) );
			$row[$this->item_sku] = intval( $entryField->get($this->item_sku) );
			if( $row[$this->item_sku] >= $qty ){
				$row[$this->item_sku.'after'] = intval($row[$this->item_sku]) - intval($qty);
			}else {
				
			}
			
            // 合算系をカウント
            @$amount['amount']   += $qty;
            @$amount['subtotal'] += $sum;
            @$amount['tax-only'] += $tax   * $qty;
            @$amount['tax-omit'] += $price * $qty;

            if ( config('shop_tax_calculate') != 'extax' ) {
                $row[$this->item_price] += $tax;
            }

            // 一応サニタイズ
            $row = $this->sanitize($row);

            // 商品エントリーへのリンクを作成
            $row += array('url' => acmsLink(array(
                'bid' => BID,
                'eid' => $row[$this->item_id],)
            ));

            // メイン画像の取得を試みる
            if ( !empty($row['entry_primary_image']) ) {
                $this->loadPrimaryImage($row['entry_primary_image'], $row);
            } else {
                $row += array(
                    'x' => $this->imageX,
                    'y' => $this->imageY,
                );
                $Tpl->add(array('noimage', 'item:loop', 'contents'));
            }
			
            // 配列からFieldに変換する
            $Field = new Field();
            $row['hash'] = $hash;
            foreach ( $row as $key => $val ) {
                $Field->set($key, $val);
            }
            $vars = $this->buildField($Field, $Tpl, array('item:loop', 'contents'));
			
			// 在庫チェック
			$EntryField = loadEntryField( intval( $row[$this->item_id] ) );
			$item_stock = $EntryField->get( $this->item_sku );
			if( isset( $item_stock ) && ( $item_stock > 0 ) ){
				if( intval( $item_stock ) < intval( $row[ $this->item_qty ] ) ){
					$Tpl->add(array($this->item_qty.':validator','item:loop', 'contents'));
				}
			}

            $Tpl->add(array('item:loop', 'contents'), $vars);
        }

        $Tpl->add('contents', $amount);

        return $Tpl->get();
    }

    function loadPrimaryImage($clid, & $vars)
    {
        $DB = DB::singleton(dsn());
    
        $SQL    = SQL::newSelect('column');
        $SQL->setSelect('column_field_2');
        $SQL->addWhereOpr('column_id', $clid);
        $filename   = $DB->query($SQL->get(dsn()), 'one');


        $path       = ARCHIVES_DIR.$filename;
        list($x, $y)    = Storage::getImageSize($path);

        /**
         * if already deleted unit. when return false.
         */
        if ( !Storage::exists($path) || $path == ARCHIVES_DIR ) return false;

        if ( max($this->imageX, $this->imageY) > max($x, $y) ) {
            $_path  = preg_replace('@(.*?)([^/]+)$@', '$1large-$2',  $path);
            if ( $xy = Storage::getImageSize($_path) ) {
                $path   = $_path;
                $x      = $xy[0];
                $y      = $xy[1];
            }
        }

        $vars   += array(
            'path'  => $path,
        );
        if ( 'on' == $this->imageTrim ) {
            $imgX   = $x;
            $imgY   = $y;
            if ( $x > $this->imageX and $y > $this->imageY ) {
                //if ( ($x - $this->imageX) < ($y - $this->imageY) ) {
                if ( ($x / $this->imageX) < ($y / $this->imageY) ) {
                    $imgX   = $this->imageX;
                    $imgY   = round($y / ($x / $this->imageX));
                } else {
                    $imgY   = $this->imageY;
                    $imgX   = round($x / ($y / $this->imageY));
                }
            } else {
                if ( $x < $this->imageX ) {
                    $imgX   = $this->imageX;
                    $imgY   = round($y * ($this->imageX / $x));
                } else if ( $y < $this->imageY ) {
                    $imgY   = $this->imageY;
                    $imgX   = round($x * ($this->imageY / $y));
                } else {
                    if ( ($this->imageX - $x) > ($this->imageY - $y) ) {
                        $imgX   = $this->imageX;
                        $imgY   = round($y * ($this->imageX / $x));
                    } else {
                        $imgY   = $this->imageY;
                        $imgX   = round($x * ($this->imageY / $y));
                    }
                }
            }
            $this->imageCenter  = 'on';
        } else {
            if ( $x > $this->imageX ) {
                if ( $y > $this->imageY ) {
                    if ( ($x - $this->imageX) < ($y - $this->imageY) ) {
                        $imgY   = $this->imageY;
                        $imgX   = round($x / ($y / $this->imageY));
                    } else {
                        $imgX   = $this->imageX;
                        $imgY   = round($y / ($x / $this->imageX));
                    }
                } else {
                    $imgX   = $this->imageX;
                    $imgY   = round($y / ($x / $this->imageX));
                }
            } else if ( $y > $this->imageY ) {
                $imgY   = $this->imageY;
                $imgX   = round($x / ($y / $this->imageY));
            } else {
                if ( 'on' == $this->imageZoom ) {
                    if ( ($this->imageX - $x) > ($this->imageY - $y) ) {
                        $imgY   = $this->imageY;
                        $imgX   = round($x * ($this->imageY / $y));
                    } else {
                        $imgX   = $this->imageX;
                        $imgY   = round($y * ($this->imageX / $x));
                    }
                } else {
                    $imgX   = $x;
                    $imgY   = $y;
                }
            }
        }

        //-------
        // align
        if ( 'on' == $this->imageCenter ) {
            if ( $imgX > $this->imageX ) {
                $left   = round((-1 * ($imgX - $this->imageX)) / 2);
            } else {
                $left   = round(($this->imageX - $imgX) / 2);
            }
            if ( $imgY > $this->imageY ) {
                $top    = round((-1 * ($imgY - $this->imageY)) / 2);
            } else {
                $top    = round(($this->imageY - $imgY) / 2);
            }
        } else {
            $left   = 0;
            $top    = 0;
        }

        $vars   += array(
            'imgX'  => $imgX,
            'imgY'  => $imgY,
            'left'  => $left,
            'top'   => $top,
        );

        //------
        // tiny
        $tiny   = ARCHIVES_DIR.preg_replace('@(.*?)([^/]+)$@', '$1tiny-$2', $filename);
        if ( $xy = Storage::getImageSize($tiny) ) {
            $vars   += array(
                'tinyPath'  => $tiny,
                'tinyX'     => $xy[0],
                'tinyY'     => $xy[1],
            );
        }

        $vars   += array(
            'x' => $this->imageX,
            'y' => $this->imageY,
        );
    
        return $vars;
    }
}