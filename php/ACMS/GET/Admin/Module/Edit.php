<?php

class ACMS_GET_Admin_Module_Edit extends ACMS_GET_Admin_Edit
{
    function auth()
    {
        if ( roleAvailableUser() ) {
            if ( !roleAuthorization('module_edit', BID) ) return false;
        } else {
            $mid = $this->Get->get('mid', null);
            if ( !sessionWithAdministration() && !Auth::checkShortcut('Module_Update', ADMIN, 'mid', $mid) ) {
                return true;
            }
        }
        return true;
    }

    function edit(& $Tpl)
    {
        $mid    = $this->Get->get('mid');
        $Module = $this->Post->getChild('module');

        if ( !sessionWithAdministration() && Auth::checkShortcut('Module_Update', ADMIN, 'mid', $mid) ) {
            $this->Post->set('shortcut', 'yes');
        }

        if ($rules = $this->getRule()) {
            $topicVars['rule'] = 1;
            foreach ($rules as $rule) {
                $Tpl->add(array('rule:loop'), array(
                    'name' => $rule['name'],
                    'rid' => $rule['id'],
                ));
            }
        }

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('module');
        $SQL->addSelect('module_blog_id');
        $SQL->addWhereOpr('module_id', $mid);
        $mbid   = $DB->query($SQL->get(dsn()), 'one');

        if (!empty($mbid) && !(0
                || sessionWithAdministration($mbid)
                || (roleAvailableUser() && roleAuthorization('module_edit', $mbid))
                || Auth::checkShortcut('Module_Update', ADMIN, 'mid', $mid)
            )
        ) {
            $Tpl->add('error#auth');
            return true;
        }
        if ( $Module->isNull() && (empty($this->edit) or ( $this->edit !== 'insert' && $this->edit !== 'delete')) ) {
            $_Module    = loadModule($mid);
            $_Module->setField('field_', $_Module->get('field'));

            if ( !!($start = $_Module->get('start')) ) {
                $date_time = explode(' ', $start);
                $date      = $date_time[0];
                $time      = $date_time[1];
                $_Module->setField('start_date', $date);
                $_Module->setField('start_time', $time);
            }
            if ( !!($end = $_Module->get('end')) ) {
                $date_time = explode(' ', $end);
                $date      = $date_time[0];
                $time      = $date_time[1];
                $_Module->setField('end_date', $date);
                $_Module->setField('end_time', $time);
            }
            $Module->overload($_Module);
        }

        $this->buildArgLabels($Module);
        $Module->set('mbid', $mbid);

        if ( in_array($Module->get('name'), array('Blog_Field', 'Entry_Field', 'Category_Field', 'User_Field')) ) {
            $Module->delete('id');
        }
/*
        if ( !isBlogGlobal(BID) ) {
            $Module->delete('scope');
        } else if ( !$Module->get('scope') ) {
            $Module->set('scope', 'local');
        }
*/
        //--------
        // field
        $Field  =& $this->Post->getChild('field');
        if ($this->Post->isNull() and !!$mid) {
            $Field->overload(loadModuleField($mid));
        }

        return true;
    }

    protected function getRule() {
        $SQL = SQL::newSelect('rule');
        $SQL->addLeftJoin('blog', 'blog_id', 'rule_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');

        $Where = SQL::newWhere();
        $Where->addWhereOpr('rule_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('rule_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);
        $SQL->addWhereOpr('rule_status', 'open');
        $SQL->setOrder('rule_sort');

        $result = array();
        $result[] = array(
            'id' => null,
            'bid' => BID,
            'name' => gettext('ルールなし'),
        );
        $all = DB::query($SQL->get(dsn()), 'all');
        foreach ($all as $item) {
            $result[] = array(
                'id' => $item['rule_id'],
                'bid' => BID,
                'name' => $item['rule_name'],
            );
        }
        return $result;
    }
}
