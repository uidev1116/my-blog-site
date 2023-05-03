<?php

class ACMS_GET_Banner extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        if ( !$aryStatus = configArray('banner_status') ) return '';

        $order      = config('banner_order');
        $loopClass  = config('banner_loop_class');

        switch ( $order ) {
            case 'random':
                $keys = array_keys($aryStatus);
                shuffle($keys);
                foreach($keys as $key){
                    $result[$key] = $aryStatus[$key];
                }
                $aryStatus = $result;
                break;
            case 'sort-desc':
                krsort($aryStatus);
                break;
            case 'sort-asc':
            default:
                break;
        }

        $limit = config('banner_limit');
        $int_display = 0;
        if ( is_numeric($limit) && intval($limit) > 0 ) {
            //$aryStatus = array_slice($aryStatus, 0, $limit, true);
        } else if ( is_array($aryStatus) ) {
            $limit = count( $aryStatus );
        } else {
            $limit = 0;
        }

        foreach ( $aryStatus as $i => $status ) {

            if ( 'open' <> $status ) continue;

            $datestart = mb_convert_kana(config('banner_datestart', '', $i),"a",'UTF-8');
            $timestart = mb_convert_kana(config('banner_timestart', '', $i),"a",'UTF-8');
            $dateend = mb_convert_kana(config('banner_dateend', '', $i),"a",'UTF-8');
            $timeend = mb_convert_kana(config('banner_timeend', '', $i),"a",'UTF-8');

            $datestart = ( strlen($datestart) > 0 )?$datestart:'0000-01-01';
            $timestart = ( strlen($timestart) > 0 )?$timestart:'00:00:00';
            $dateend = ( strlen($dateend) > 0 )?$dateend:'9999-12-31';
            $timeend = ( strlen($timeend) > 0 )?$timeend:'23:59:59';

            if( ! ( ( ($datestart . ' ' . $timestart) <= date('Y-m-d H:i:s', requestTime()) ) && ( date('Y-m-d H:i:s', requestTime()) <= ($dateend . ' ' . $timeend) ) ) ) {
                continue;
            }

            $int_display++;
            if ( $int_display > $limit ) break;

            if ( $img = config('banner_img', '', $i) ) {
                $xy = Storage::getImageSize(ARCHIVES_DIR.$img);
                $Tpl->add('banner#img', array(
                    'img'   => $img,
                    'x'     => isset($xy[0]) ? $xy[0] : '',
                    'y'     => isset($xy[1]) ? $xy[1] : '',
                    'url'   => config('banner_url', '', $i),
                    'alt'   => config('banner_alt', '', $i),
                    'attr1' => config('banner_attr1', '', $i),
                    'attr2' => config('banner_attr2', '', $i),
                    'target'=> config('banner_target', '', $i),
                    'nth'   => $i,
                    'banner:loop.class' => $loopClass,
                ));
            } else {
                $Tpl->add('banner#src', array(
                    'src'   => config('banner_src', '', $i),
                    'nth'   => $i,
                    'banner:loop.class' => $loopClass,
                ));
            }
            $Tpl->add('banner:loop', array(
                'banner:loop.class' => $loopClass,
            ));
        }

        return setGlobalVars($Tpl->get());
    }
}
