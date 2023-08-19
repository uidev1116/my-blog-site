<?php

namespace Acms\Services\Session;

use DB;
use SQL;

class DatabaseHandler
{
    /**
     * @var string
     */
    private $savePath;

    /**
     * @param $savePath
     * @param $sessionName
     *
     * @return bool
     */
    function open($savePath, $sessionName)
    {
        $this->savePath = $savePath;

        return true;
    }

    /**
     * @return bool
     */
    function close()
    {
        return true;
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    function read($id)
    {
        $SQL = SQL::newSelect('session_php');
        $SQL->addSelect('session_data');
        $SQL->addWhereOpr('session_id', $id);
        $data = DB::query($SQL->get(dsn()), 'one');

        return $data ? $data : '';
    }

    /**
     * @param $id
     * @param $data
     *
     * @return bool
     */
    function write($id, $data)
    {
        $SQL = SQL::newSelect('session_php');
        $SQL->addSelect('session_id');
        $SQL->addWhereOpr('session_id', $id);

        if (DB::query($SQL->get(dsn()), 'one')) {
            $SQL = SQL::newUpdate('session_php');
            $SQL->addUpdate('session_expire', REQUEST_TIME);
            $SQL->addUpdate('session_data', $data);
            $SQL->addWhereOpr('session_id', $id);
        } else {
            $SQL = SQL::newInsert('session_php');
            $SQL->addInsert('session_id', $id);
            $SQL->addInsert('session_expire', REQUEST_TIME);
            $SQL->addInsert('session_data', $data);
        }
        DB::query($SQL->get(dsn()), 'exec');

        return (DB::affected_rows() > 0);

    }

    /**
     * @param $id
     *
     * @return bool
     */
    function destroy($id)
    {
        $SQL = SQL::newDelete('session_php');
        $SQL->addWhereOpr('session_id', $id);
        DB::query($SQL->get(dsn()), 'exec');

        return true;
    }

    /**
     * @param $maxlifetime
     *
     * @return bool
     */
    function gc($maxlifetime)
    {
        $SQL = SQL::newDelete('session_php');
        $SQL->addWhereOpr('session_expire', REQUEST_TIME - $maxlifetime, '<');
        DB::query($SQL->get(dsn()), 'exec');

        return true;
    }
}
