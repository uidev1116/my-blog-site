<?php

class ACMS_POST_Shop_Cart_Add extends ACMS_POST_Shop
{
    function post()
    {
        $this->initVars();

        $Cart = $this->extract('cart');
        $Cart->setMethod($this->item_id, 'required');
        $Cart->setMethod($this->item_id, 'digits');
        $Cart->setMethod($this->item_qty, 'required');
        $Cart->setMethod($this->item_qty, 'digits');
        $Cart->setMethod('cart_bid', 'digits');
    	
        $Cart->validate(new ACMS_Validator());

        $bid    = $Cart->isNull('cart_bid') ? BID : intval($Cart->get('cart_bid', BID));

        if ( $this->Post->isValidAll() ) {

            $TEMP = $this->openCart($bid);

            $DB = DB::singleton(dsn());
            $SQL = SQL::newSelect('entry');
            $SQL->addLeftJoin('field', 'field_eid', 'entry_id');
            $SQL->addSelect('field_key');
            $SQL->addSelect('field_value');
            $SQL->addSelect('entry_primary_image');
            $SQL->addSelect('entry_category_id');
            $SQL->addWhereOpr('entry_id', $Cart->get($this->item_id));

            $fds = $DB->query($SQL->get(dsn()), 'all');

            if ( !empty($fds) ) {
                // レパートリーの指定を取得する
                $repertories = $Cart->getArray('item_repertory_fields');

                // カート内の商品情報フィールドに配列型は転写できない（efdに選択肢として配列を登録することはできる）
                foreach ( $fds as $fd ) {
                    $item[$fd['field_key']] = $fd['field_value'];
                }

                // すべての行にentry系の情報はつながっている
                $item['entry_primary_image'] = $fd['entry_primary_image'];
                $item[$this->item_category]  = ACMS_RAM::categoryName(intval($fd['entry_category_id']));

                // 既定のフィールドを埋める
                $item[$this->item_qty] = intval($Cart->get($this->item_qty));
                $item[$this->item_id]  = intval($Cart->get($this->item_id));
                $item[$this->item_price.'#tax'] = $this->tax($item[$this->item_price]);

                // 直前に追加した商品の情報を保存
                $_SESSION['added'] = $item;

                // 商品データを組み立てる（既定のフィールドは前に追加しているので破棄）
                $Cart->delete($this->item_qty);
                $Cart->delete($this->item_id);
                $Cart->delete('item_repertory_fields'); // レパートリーの定義も不要なので破棄する
                $fds = $Cart->listFields();
                foreach ( $fds as $fd ) {
                    $item[$fd] = $Cart->get($fd);
                }

                //　既存のカート内商品の走査
				if( ! is_array( $TEMP ) )$TEMP = array();
				
                foreach ( $TEMP as $p => $inItem ) {
                    // レパートリー含めて同じ商品があれば，既存の記録を破棄して，数量だけマージ
                    if ( $this->isSameItem($inItem, $item, $repertories) )
                    {
                        $item[$this->item_qty] += $inItem[$this->item_qty];
                        unset($TEMP[$p]);
                    }
                }

                // 外税でなければ，単価からは税金分を引いておく
                if ( config('shop_tax_calculate') != 'extax' ) {
                    @$item[$this->item_price] -= $item[$this->item_price.'#tax'];
                }

                // 商品の小計
                @$item[$this->item_price.'#sum'] += intval(
                    ( ($item[$this->item_price] + $item[$this->item_price.'#tax']) * $item[$this->item_qty] ));

                // ハッシュを配列キーにあたえる（ユーザー内だから単一時間軸内でユニークであればOK）
                $TEMP[md5(time())] = $item;
            }

            $this->closeCart($TEMP, $bid);

            // redirect to target location
            if ( config('shop_cart_elevate') == 'on' ) {
                $this->screenTrans($this->addedTpl, null, $bid);
            } else {
                $this->screenTrans($this->addedTpl);
            }

        } else {
            return $this->Post;
        }
    }

    // レパートリーの定義は，判定したい商品の追加時には，必ず同じモノが提供されなければならない
    function isSameItem($old, $new, $repertories)
    {
        // エントリーIDが異なれば，違う商品とみなす
        if ( $old[$this->item_id] !== $new[$this->item_id] ) return false;

        // エントリーIDが異なることが確認された上で，レパートリーの定義がなければ，同じ商品とみなす
        if ( empty($repertories) ) return true;

        foreach ( $repertories as $repertory ) {
            // もしもいずれかでレパートリーが未定義であれば，違う商品とみなす
            if ( !isset($old[$repertory]) || !isset($new[$repertory]) ) return false;

            // 何らかのレパートリーが異なれば，違う商品とみなす
            if ( $old[$repertory] !== $new[$repertory] ) return false;
        }

        // すべてのレパートリーに一致が見られれば，同じ商品とみなす
        return true;
    }

    function tax($val)
    {
        $rate    = config('shop_tax_rate');
        $tax_int = $rate * 100;

        if ( config('shop_tax_calculate') == 'intax' ) {
            return intval(floor($val * ($tax_int / (100 + $tax_int))));
        } elseif ( config('shop_tax_calculate') == 'extax' ) {
            return intval(floor($val * $rate));
        }
    }
}