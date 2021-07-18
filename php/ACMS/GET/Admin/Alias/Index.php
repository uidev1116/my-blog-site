<?php

/*
 * aid:null を aid:0 として扱う
 * htmlのフォーム上で <input type="checkbox" name="..." value="{aid}" />
 * とあるときに、value=""が値がなくなってしまうため。
 */

class ACMS_GET_Admin_Alias_Index extends ACMS_GET_Admin
{
    function get()
    {
        if ( 'alias_index' <> ADMIN ) { return ''; }
        if ( !sessionWithAdministration() ) { return false; }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        //-----
        // msg
        if ( !$this->Post->isNull() ) {
            if ( $this->Post->isValidAll() ) { 
                $Tpl->add('msg#success');
            } else {
                $Tpl->add('msg#error', $this->buildField($this->Post, $Tpl, 'msg#error'));
            }
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('alias');
        $SQL->addWhereOpr('alias_blog_id', BID, '=', 'OR');
        $SQL->addWhereOpr('alias_scope', 'global', '=', 'OR');
        $SQL->setOrder('alias_sort');
        $all    = $DB->query($SQL->get(dsn()), 'all');

        $offset = intval(ACMS_RAM::blogAliasSort(BID)) - 1;
        if ( 0 > $offset ) { $offset = 0; }
        array_splice($all, $offset, 0, array(array(
            'alias_name'    => ACMS_RAM::blogName(BID),
            'alias_domain'  => ACMS_RAM::blogDomain(BID),
            'alias_code'    => ACMS_RAM::blogCode(BID),
            'alias_status'  => ACMS_RAM::blogAliasStatus(BID),
            'alias_id'      => 0,
        )));

        $primary    = intval(ACMS_RAM::blogAliasPrimary(BID));
        $count      = count($all);

        foreach ( $all as $i => $row ) {
            $aid    = intval($row['alias_id']);
            $abid   = isset($row['alias_blog_id']) ? $row['alias_blog_id'] : 1;
            $scope  = isset($row['alias_scope']) ? $row['alias_scope'] : 'local';
            $url    = 'http://'.$row['alias_domain'];
            $extend = ( 1
                && $scope === 'global'
                && ACMS_RAM::blogLeft(BID) > ACMS_RAM::blogLeft($abid)
                && ACMS_RAM::blogRight(BID) < ACMS_RAM::blogRight($abid)
            ) ? true : false;

            if ( 1
                && ACMS_RAM::blogLeft(BID) < ACMS_RAM::blogLeft($abid)
                && ACMS_RAM::blogRight(BID) > ACMS_RAM::blogRight($abid)
            ) {
                continue;
            }

            // 元ブログのドメインと同じなら，スクリプトルートは同一とみなし，DIR_OFFSETを適用する
            if ( 1
                && ACMS_RAM::blogDomain(BID) === $row['alias_domain']
                && DIR_OFFSET
            ) {
                $url    .= '/'.rtrim(DIR_OFFSET, '/');
            }
            if ( !empty($row['alias_code']) ) {
                $url    .= '/'.$row['alias_code'];
            }
            $url    .= '/';
            $name       = $row['alias_name'];
            $domain     = $row['alias_domain'];
            $code       = $row['alias_code'];
            $bcode      = ACMS_RAM::blogCode(BID).'/';

            if ( $extend ) {
                $url    .= $bcode;
                $code   .= $bcode;
            }

            $var    = array(
                'sort'         => $i+1,
                'name'      => $name,
                'domain'    => $domain,
                'code'      => $code,
                'aid'       => $row['alias_id'],
                'urlLable'  => $url,
                'urlValue'  => $url.SESSION_NAME.'/'.SESSION_OLD_NEXT_ID.'/',
            );

            if ( $extend ) {
                $var['disabled'] = config('attr_disabled');
                $Tpl->add('action#root');
            } else if ( !empty($aid) ) {
                $var['aidLabel']    = $aid;
                $var['itemUrl'] = acmsLink(array(
                    'bid'   => BID,
                    'admin' => 'alias_edit',
                    'query' => array(
                        'aid'   => $aid,
                    ),
                ));
            } else {
                $Tpl->add('aid#null');
                $Tpl->add('action#default');
            }

            if ( $primary == $aid ) {
                $var['aid:checked'] = config('attr_checked');
            }

            $Tpl->add(array('status:touch#'.$row['alias_status']));

            if ( 1
                and isset($row['alias_scope'])
                and $row['alias_scope'] === 'global'
            ) {
                $Tpl->add(array('scope:touch#global'));
            } else {
                $Tpl->add(array('scope:touch#local'));
            }

            for ( $j=0; $j<$count; $j++ ) {
                $value  = $j + 1;
                $_var   = array(
                    'value' => $value,
                    'label' => $value,
                );
                if ( $i == $j ) { $_var['selected'] = config('attr_selected'); } 

                $Tpl->add('sort:loop', $_var);
            }
            
            $Tpl->add('alias:loop', $var);
        }

        if ( !$this->Post->isNull() ) {
            $Tpl->add(null, array('notice_mess' => 'show'));
        }

        return $Tpl->get();
    }
}

