<?php

class ACMS_GET_Admin_Media_Tag_Index extends ACMS_GET_Admin
{
    function get()
    {

        if ('media-tag_index' <> ADMIN) {
            return false;
        }
        if (roleAvailableUser()) {
            if (!roleAuthorization('tag_edit', BID)) {
                return false;
            }
        } else {
            if (!sessionWithCompilation()) {
                return false;
            }
        }

        $order  = ORDER ? ORDER : config('admin_tag_order');
        $limits = configArray('admin_tag_limit_option');
        $limit  = LIMIT ? LIMIT : $limits[config('admin_tag_limit')];

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());

        $vars   = array();

        //-------
        // order
        $vars['order:selected#' . $order]  = config('attr_selected');

        //-------
        // limit
        foreach ($limits as $val) {
            $_vars  = array(
                'value' => $val,
                'label' => $val,
            );
            if ($limit == $val) {
                $_vars['selected'] = config('attr_selected');
            }
            $Tpl->add('limit:loop', $_vars);
        }

        $SQL    = SQL::newSelect('media_tag');
        $SQL->setSelect(SQL::newFunction('media_tag_name', 'DISTINCT'), 'media_tag_amount', null, 'COUNT');
        $SQL->addWhereOpr('media_tag_blog_id', BID);
        if (!$pageAmount = $DB->query($SQL->get(dsn()), 'one')) {
            $Tpl->add('index#notFound');
            $Tpl->add(null, $vars);
            return $Tpl->get();
        }

        $vars += $this->buildPager(
            PAGE,
            $limit,
            $pageAmount,
            config('admin_pager_delta'),
            config('admin_pager_cur_attr'),
            $Tpl,
            array(),
            array('admin' => ADMIN)
        );

        $SQL = SQL::newSelect('media_tag');
        $SQL->addSelect('media_tag_name');
        $SQL->addSelect('media_tag_name', 'media_tag_amount', null, 'count');
        $SQL->addWhereOpr('media_tag_blog_id', BID);
        $SQL->setGroup('media_tag_name');
        ACMS_Filter::mediaTagOrder($SQL, $order);
        $SQL->setLimit($limit, (PAGE - 1) * $limit);
        $q  = $SQL->get(dsn());

        $DB->query($q, 'fetch');
        while ($row = $DB->fetch($q)) {
            $tag    = $row['media_tag_name'];
            $Tpl->add('tag:loop', array(
                'url' => acmsLink(array(
                    'admin' => 'media-tag_edit',
                    'tag' => $tag,
                )),
                'name' => $tag,
                'amount' => $row['media_tag_amount'],
            ));
        }

        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
