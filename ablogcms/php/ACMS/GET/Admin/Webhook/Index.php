<?php

class ACMS_GET_Admin_Webhook_Index extends ACMS_GET_Admin
{
    function get()
    {
        if (!sessionWithAdministration()) return false;

        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        if (!HOOK_ENABLE) {
            $Tpl->add('disabled');
            return $Tpl->get();
        }

        $sql = SQL::newSelect('webhook');
        $sql->addLeftJoin('blog', 'blog_id', 'webhook_blog_id');
        ACMS_Filter::blogTree($sql, BID, 'ancestor-or-self');

        $where  = SQL::newWhere();
        $where->addWhereOpr('webhook_blog_id', BID, '=', 'OR');
        $where->addWhereOpr('webhook_scope', 'global', '=', 'OR');
        $sql->addWhere($where);
        $sql->addOrder('webhook_id', 'DESC');
        $q = $sql->get(dsn());

        if (!DB::query($q, 'fetch') || !($row = DB::fetch($q)) ) {
            $Tpl->add('notFound');
            return $Tpl->get();
        }

        do {
            $id = intval($row['webhook_id']);
            $bid = intval($row['webhook_blog_id']);

            if (BID !== $bid) {
                $row['webhook_scope'] = 'parental';
            }
            $Tpl->add('scope#' . $row['webhook_scope']);
            $Tpl->add('status#' . $row['webhook_status']);
            $vars = array(
                'id' => $id,
                'bid' => $bid,
                'name' => $row['webhook_name'],
                'scope' => $row['webhook_scope'],
                'type' => $row['webhook_type'],
                'url' => $row['webhook_url'],
            );

            if (BID === $bid) {
                $Tpl->add('mine', $this->getLinkVars(BID, $row));
            } else if (sessionWithAdministration($bid)) {
                $Tpl->add('notMinePermit', $this->getLinkVars($bid, $row));
            } else {
                $Tpl->add('notMine');
            }
            $Tpl->add('webhook:loop', $vars);
        } while ($row = DB::fetch($q));

        return $Tpl->get();
    }

    protected function getLinkVars($bid, $webhook)
    {
        $id = intval($webhook['webhook_id']);
        return array(
            'itemUrl' => acmsLink(array(
                'bid' => $bid,
                'admin' => 'webhook_edit',
                'query' => array(
                    'id' => $id,
                ),
            )),
            'logUrl' => acmsLink(array(
                'bid' => $bid,
                'admin' => 'webhook_log',
                'query' => array(
                    'id' => $id,
                ),
            )),
        );
    }
}
