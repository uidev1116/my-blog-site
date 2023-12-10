<?php

namespace Acms\Services\Logger;

use DB;
use SQL;
use SQL_Select;

class Repository
{
    public function getIndexSql(int $limit, int $page, array $levels, int $suid, string $start = START, string $end = END): array
    {
        $sql = SQL::newSelect('audit_log');
        if (empty($levels)) {
            $sql->addWhereOpr('audit_log_level_name', 'DEBUG', '<>');
        } else {
            $sql->addWhereIn('audit_log_level_name', $levels);
        }
        if (!editionWithProfessional()) {
            $sql->addWhereOpr('audit_log_level_name', 'INFO', '<>');
        }
        if (!empty($suid)) {
            $sql->addWhereOpr('audit_log_session_uid', $suid);
        }
        if ($start && strpos($start, '1000-01-01') === false) {
            $sql->addWhereOpr('audit_log_datetime', date('Y-m-d 00:00:00', strtotime($start)), '>');
        }
        if ($end && strpos($end, '9999-12-31') === false) {
            $sql->addWhereOpr('audit_log_datetime', date('Y-m-d 23:59:59', strtotime($end)), '<=');
        }
        $sql->setOrder('audit_log_datetime', 'DESC');

        $amount = new SQL_Select($sql);
        $amount->setSelect('*', 'log_amount', null, 'count');
        $count = intval(DB::query($amount->get(dsn()), 'one'));

        $sql->setLimit($limit, ($page - 1) * $limit);

        return [$sql, $count];
    }
}
