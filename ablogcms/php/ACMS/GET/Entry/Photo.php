<?php

class ACMS_GET_Entry_Photo extends ACMS_GET_Entry_Summary
{
    function initVars()
    {
        return array(
            'order'            => array(
                $this->order ? $this->order : config('entry_photo_order'),
                config('entry_photo_order2'),
            ),
            'orderFieldName'   => config('entry_photo_order_field_name'),
            'noNarrowDownSort' => config('entry_photo_no_narrow_down_sort', 'off'),
            'limit'            => intval(config('entry_photo_limit')),
            'offset'           => intval(config('entry_photo_offset')),
            'indexing'         => config('entry_photo_indexing'),
            'membersOnly'      => config('entry_photo_members_only'),
            'subCategory'      => config('entry_photo_sub_category'),
            'secret'           => config('entry_photo_secret'),
            'newtime'          => config('entry_photo_newtime'),
            'unit'             => config('entry_photo_unit'),
            'notfound'         => config('mo_entry_photo_notfound'),
            'notfoundStatus404'=> config('entry_photo_notfound_status_404'),
            'noimage'          => config('entry_photo_noimage'),
            'imageX'           => intval(config('entry_photo_image_x')),
            'imageY'           => intval(config('entry_photo_image_y')),
            'imageTrim'        => config('entry_photo_image_trim'),
            'imageZoom'        => config('entry_photo_image_zoom'),
            'imageCenter'      => config('entry_photo_image_center'),
            'pagerDelta'       => config('entry_photo_pager_delta'),
            'pagerCurAttr'     => config('entry_photo_pager_cur_attr'),
            'hiddenCurrentEntry'    => config('entry_photo_hidden_current_entry'),
            'hiddenPrivateEntry'    => config('entry_photo_hidden_private_entry'),
            'loop_class'            => config('entry_photo_loop_class'),
        );
    }
}
