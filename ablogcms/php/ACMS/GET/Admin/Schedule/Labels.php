<?php

class ACMS_GET_Admin_Schedule_Labels extends ACMS_GET_Admin
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $scid   = $this->Get->get('scid');

        $config = Config::loadDefaultField();
        $config->overload(Config::loadBlogConfig(BID));

        $labels     = $config->getArray('schedule_label@'.$scid);
        $takeover   = $this->Post->getChild('schedule');
        $isNull     = $takeover->listFields();
        $add  = 3;
        $sort = 0;
        $max  = count($labels) + 1 + $add;

        if ( is_array($labels) && !empty($labels) ) {

            foreach ( $labels as $sort => $label ) {
                $sort++;
                $_label = explode(config('schedule_label_separator'), $label);

                for ( $i=1; $i < $max; $i++ ) {
                    $vars   = array('i' => $i);
                    if ( $i == $sort ) $vars['selected'] = config('attr_selected');
                    $Tpl->add(array('sort:loop', 'label:loop'), $vars);
                }

                $Tpl->add('label:loop', array(
                    'sort'  => $sort,
                    'name'  => isset($_label[0]) ? $_label[0] : '',
                    'key'   => isset($_label[1]) ? $_label[1] : '',
                    'class' => isset($_label[2]) ? $_label[2] : '',
                    )
                );
            }

            for ( $n=0; $n<$add; $n++ ) {
                $sort++;
                for ( $i=1; $i < $max; $i++ ) {
                    $vars   = array('i' => $i);
                    if ( $i == $sort ) $vars['selected'] = config('attr_selected');
                    $Tpl->add(array('sort:loop', 'label:loop'), $vars);
                }
                $Tpl->add('label:loop');
            }
        } else if ( $this->Get->get('edit') == 'update' ) {
            for ( $n=0; $n<$add; $n++ ) {
                $sort++;
                for ( $i=1; $i < $max; $i++ ) {
                    $vars   = array('i' => $i);
                    if ( $i == $sort ) $vars['selected'] = config('attr_selected');
                    $Tpl->add(array('sort:loop', 'label:loop'), $vars);
                }
                $Tpl->add('label:loop');
            }
        } else {
            $Tpl->add('notFound');
        }

        return $Tpl->get();
    }
}
