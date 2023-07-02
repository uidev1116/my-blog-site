<?php

class ACMS_GET_Api_Twitter_Statuses extends ACMS_GET_Api_Twitter
{
    function statuses()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $this->buildModuleField($Tpl);

        $this->resolveRequest($Tpl, 'twitter');

        return $Tpl->get();
    }

    function build($response, $Tpl)
    {
        $json = json_decode($response);

        if ( $json === false ) {
            $Tpl->add('unavailable');
            return false;
        }

        if ( count($json) === 0 ) {
            $Tpl->add('notFound');
            return false;
        }

        $loop  = 0;
        $args  = array();

        foreach ( $json as $row ) {
            if ( 'true' == $row->user->protected && 'on' == $this->ignore ) {
                continue;
            }

            $vars   = array(
                'text'      => $row->text,
                'name'      => $row->user->name,
                'screen_name'=> $row->user->screen_name,
                'user_id'   => $row->user->id_str,
                'status_id' => $row->id_str,
                'image'     => $row->user->profile_image_url,
                'l-image'   => $this->largeImageUrl($row->user->profile_image_url),
                'permalink' => ACMS_GET_Api_Twitter::WEB_URL.$row->user->screen_name.'/status/'.$row->id_str,
            );
            $vars  += $this->buildDate($row->created_at, $Tpl, 'tweet:loop');

            $Tpl->add('tweet:loop', $vars);
            $loop++;

            if ( $loop == 1 ) {
                $args['first_id'] = $row->id_str;
            } elseif ( $loop == count($json) ) {
                $args['last_id']  = $row->id_str;
            }
        }

        /**
         * build pagger
         */
        $Tpl->add('pager', $args);

        /**
         * merge user detail
         */
        if ( $this->Field->isExists('screen_name') || $this->Field->isExists('user_id') )
        {
            $user   = array(
                'name'      => $row->user->name,
                'screen_name'=> $row->user->screen_name,
                'user_id'   => $row->user->id,
                'image'     => $row->user->profile_image_url,
                'l-image'   => $this->largeImageUrl($row->user->profile_image_url),
            );
            $Tpl->add('user', $user);
        }

        /**
         * merge fields
         */
        $fds    = $this->Field->listFields();
        $field  = array();
        foreach ( $fds as $fd ) {
            $field[$fd] = $this->Field->get($fd);
        }

        $Tpl->add(null, $field);
    }
}
