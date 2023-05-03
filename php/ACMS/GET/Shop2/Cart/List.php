<?php

class ACMS_GET_Shop2_Cart_List extends ACMS_GET_Shop2
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
        $TEMP   = $this->openCart();
        $Tpl    = $this->buildList($TEMP);
        $this->closeCart($TEMP);

        return $Tpl;
    }

    function buildList(& $TEMP)
    {
        $step   = $this->Post->get('step', null);
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $amount = array(
            'amount' => 0,
            'subtotal' => 0,
            'tax-omit' => 0,
            'tax-only' => 0,
        );

        // カートが空のときのパターン
        if ( empty($TEMP) ) {
            $Tpl->add('notFound');
            return $Tpl->get();
        }


        // 商品を削除するときのパターン
        if ( $this->Get->isExists('delete') ) {
            $delete_target = $this->Get->get('delete');
            $this->session->set('delete', $TEMP[$delete_target]);
            $this->session->save();
            unset($TEMP[$delete_target]);
            $this->closeCart($TEMP);

            $loc = preg_replace('@\?\S*@','',REQUEST_URL);
            redirect($loc);
        }

        $tax_rate = array();

        // 通常の表示をするパターン
        foreach ( $TEMP as $hash => $row ) {
            if ( 0
                or !isset($row[$this->item_price])
                or !isset($row[$this->item_qty])
            ) {
                continue;
            }

            $price  = $row[$this->item_price];
            $qty    = $row[$this->item_qty];
            $sum    = $row[$this->item_price.'#sum'];
            $rate   = $row[$this->item_price.'#rate'];

            $tax = 0;
            if (isset($row[$this->item_price.'#tax'])) {
                $tax = $row[$this->item_price.'#tax'];
            }

            $entryField = loadEntryField( intval( $row[$this->item_id] ) );
            $row[$this->item_sku] = intval( $entryField->get($this->item_sku) );
            if( $row[$this->item_sku] >= $qty ){
                $row[$this->item_sku.'after'] = intval($row[$this->item_sku]) - intval($qty);
            }else {

            }
            $amount['amount'] += $qty;

            if (!isset($amount['tax-omit'.$rate])) {
                $amount['tax-omit'.$rate] = 0;
            }
            if (!isset($amount['tax-only'.$rate])) {
                $amount['tax-only'.$rate] = 0;
            }

            if ( config('shop_tax_calc_method') == 'pileup') {

                // 商品毎に消費税を計算
                if ( config('shop_tax_calculate') == 'extax' ) {

                    @$amount['subtotal'] += $sum + $tax;
                    @$amount['tax-omit'] += $sum;
                    @$amount['tax-only'] += $tax;

                    @$amount['tax-omit'.$rate] += $sum;
                    @$amount['tax-only'.$rate] += $tax;

                } else {

                    @$amount['subtotal'] += $sum;
                    @$amount['tax-omit'] += $sum -$tax;
                    @$amount['tax-only'] += $tax;

                    @$amount['tax-omit'.$rate] += $sum -$tax;
                    @$amount['tax-only'.$rate] += $tax;
                }


            } else {

                // 小計毎に消費税を計算

                @$amount['subtotal'] += $sum; // 外税時には再計算

                @$amount['tax-omit'.$rate] += $sum;
                @$amount['tax-only'.$rate] += $tax;

                array_push ($tax_rate, $rate);

            }

            // 一応サニタイズ
            $row = $this->sanitize($row);

            // 商品エントリーへのリンクを作成
            $eid = $row[$this->item_id];
            $row += array('url' => acmsLink(array(
                'bid' => ACMS_RAM::entryBlog($eid),
                'eid' => $eid)
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

            if ( config('shop_tax_calc_method') == 'pileup' ) { // 商品毎に消費税を計算
                foreach ( $row as $key => $val ) {
                    $Field->set($key, $val);
                }
            } else {
                foreach ( $row as $key => $val ) {
                    if ($key != "item_price#tax") { // 商品毎の消費税を消す
                        $Field->set($key, $val);
                    }
                }
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

        if ( config('shop_tax_calc_method') == 'rebate' ) {

            // 小計毎に消費税を計算

            $amount['tax-omit'] = 0;
            $amount['tax-only'] = 0;

            $tax_rate = array_unique($tax_rate);

            if ( config('shop_tax_calculate') == 'intax' ) {

                //内税 intax

                foreach ( $tax_rate as $rate ) {

                    $rate_num = $rate / 100 + 1;
                    $sum = $amount['tax-omit'.$rate];

                    if ( config('shop_tax_rounding') == 'ceil' ) {
                        // 切り上げ
                        $tax = intval(ceil( $sum - ( $sum / $rate_num )));

                    } elseif ( config('shop_tax_rounding') == 'round' ) {
                        // 四捨五入
                        $tax = intval(round( $sum - ( $sum / $rate_num )));

                    } else {
                        // 切り捨て
                        $tax = intval(floor( $sum - ( $sum / $rate_num )));
                    }

                    $amount['tax-omit'.$rate] = $sum - $tax;
                    $amount['tax-only'.$rate] = $tax;

                    $amount['tax-omit'] += $amount['tax-omit'.$rate];
                    $amount['tax-only'] += $amount['tax-only'.$rate];

                }

            } else {

                //外税 extax
                $amount['subtotal'] = 0;

                foreach ( $tax_rate as $rate ) {

                    $rate_num = $rate / 100;
                    $sum = $amount['tax-omit'.$rate];

                    if ( config('shop_tax_rounding') == 'ceil' ) {
                        // 切り上げ
                        $tax = intval(ceil( $sum * $rate_num ));

                    } elseif ( config('shop_tax_rounding') == 'round' ) {
                        // 四捨五入
                        $tax = intval(round( $sum * $rate_num ));

                    } else {
                        // 切り捨て
                        $tax = intval(floor( $sum * $rate_num ));
                    }

                    $amount['tax-omit'.$rate] = $sum;
                    $amount['tax-only'.$rate] = $tax;

                    $amount['tax-omit'] += $amount['tax-omit'.$rate];
                    $amount['tax-only'] += $amount['tax-only'.$rate];

                    $amount['subtotal'] += $sum + $tax;
                }

            }

        }

        if ( config('shop_tax_no') ) {
            $amount['shop_tax_no'] = config('shop_tax_no');
        }

        $amount['shop_tax_calculate'] = config('shop_tax_calculate');
        $amount['shop_tax_calc_method'] = config('shop_tax_calc_method');
        $amount['shop_tax_rounding'] = config('shop_tax_rounding');


        $Tpl->add('contents', $amount);

        return $Tpl->get();
    }

    function loadPrimaryImage($clid, & $vars)
    {
        $DB = DB::singleton(dsn());

        $SQL = SQL::newSelect('column');
        $SQL->addWhereOpr('column_id', $clid);
        $unit = $DB->query($SQL->get(dsn()), 'row');
        $path = false;
        $filename = $unit['column_field_2'];
        $type = detectUnitTypeSpecifier($unit['column_type']);
        if ($type === 'image') {
            $path = ARCHIVES_DIR . $filename;
        } else if ($type === 'media') {
            $SQL = SQL::newSelect('media');
            $SQL->setSelect('media_path');
            $SQL->addWhereOpr('media_id', $unit['column_field_1']);
            $path = MEDIA_LIBRARY_DIR . $DB->query($SQL->get(dsn()), 'one');
        }
        /**
         * if already deleted unit. when return false.
         */
        if (empty($path) || !Storage::exists($path) || $path === ARCHIVES_DIR || $path === MEDIA_LIBRARY_DIR) {
            return false;
        }
        list($x, $y)    = Storage::getImageSize($path);

        if (max($this->imageX, $this->imageY) > max($x, $y)) {
            $_path  = preg_replace('@(.*?)([^/]+)$@', '$1large-$2',  $path);
            if ( $xy = Storage::getImageSize($_path) ) {
                $path   = $_path;
                $x      = $xy[0];
                $y      = $xy[1];
            }
        }

        $vars += array(
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
        if ($type === 'image') {
            $tiny = ARCHIVES_DIR.preg_replace('@(.*?)([^/]+)$@', '$1tiny-$2', $filename);
            if ( $xy = Storage::getImageSize($tiny) ) {
                $vars   += array(
                    'tinyPath'  => $tiny,
                    'tinyX'     => $xy[0],
                    'tinyY'     => $xy[1],
                );
            }
        }
        $vars   += array(
            'x' => $this->imageX,
            'y' => $this->imageY,
        );

        return $vars;
    }
}
