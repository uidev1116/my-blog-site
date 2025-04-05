<?php

class ACMS_POST_Log_Form_Download extends ACMS_POST
{
    public function post()
    {
        if (!$this->authorityCheck()) {
            return false;
        }

        $fmid   = intval($this->Get->get('fmid'));
        $serial = intval($this->Get->get('serial'));
        $order  = $this->Q->get('order', 'datetime-asc');
        $eid    = $this->Post->get('eid');

        $form   = $this->getFormData($fmid);

        if (empty($form)) {
            return false;
        }

        // ログ取得のクエリ作成
        $query  = $this->buildQuery($fmid, $eid, $serial, $order);

        // データ修正
        $data   = $this->fixData($query);

        // CSVの作成
        $csv    = $this->buildCSV($query, $data);

        // ダウンロード
        $this->download($csv);
    }

    /**
     * 権限チェック
     *
     * @return boolean
     */
    function authorityCheck()
    {
        if (!sessionWithFormAdministration()) {
            return false;
        }
        if (!IS_LICENSED) {
            return false;
        }
        if (!($fmid = intval($this->Get->get('fmid')))) {
            return false;
        }

        return true;
    }

    /**
     * フォームデータの取得
     *
     * @param int $fmid
     * @return array
     */
    function getFormData($fmid)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('form');
        $SQL->addSelect('form_name');
        $SQL->addSelect('form_blog_id');
        $SQL->addWhereOpr('form_id', $fmid);
        $SQL->addLeftJoin('blog', 'blog_id', 'form_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');

        $Where  = SQL::newWhere();
        $Where->addWhereOpr('form_blog_id', BID, '=', 'OR');
        $Where->addWhereOpr('form_scope', 'global', '=', 'OR');
        $SQL->addWhere($Where);

        return $DB->query($SQL->get(dsn()), 'row');
    }

    /**
     * ログデータ取得の為のクエリを発行
     *
     * @param int $fmid
     * @param int $eid
     * @param int $serial
     * @param string $order
     * @return array
     */
    function buildQuery($fmid, $eid, $serial, $order)
    {
        $SQL    = SQL::newSelect('log_form');
        $SQL->addLeftJoin('blog', 'blog_id', 'log_form_blog_id');
        ACMS_Filter::blogTree($SQL, BID, 'ancestor-or-self');

        $SQL->addWhereOpr('log_form_form_id', $fmid);
        if (!empty($eid)) {
            $SQL->addWhereOpr('log_form_entry_id', $eid);
        }
        if (!empty($serial)) {
            $SQL->addWhereOpr('log_form_serial', $serial);
        }

        $SQL->addWhereBw('log_form_datetime', START, END);
        $SQL->setOrder('log_form_datetime', ('datetime-asc' == $order) ? 'DESC' : 'ASC');

        return $SQL->get(dsn());
    }

    /**
     * データの修正
     *
     * @param string $query
     * @return array
     */
    function fixData($query)
    {
        $DB = DB::singleton(dsn());
        $DB->query($query, 'fetch');

        $aryFd    = [];
        while ($row = $DB->fetch($query)) {
            if (isset($row['log_form_version']) && intval($row['log_form_version']) === 1) {
                $Field = acmsDangerUnserialize($row['log_form_data']);
                if ($Field instanceof Field) {
                    $aryFd = array_unique(array_merge($aryFd, $Field->listFields()));
                }
            }
        }
        $aryFd[] = 'log_form_datetime';

        $atPathAry = [];
        foreach ($aryFd as $i => $fd) {
            // @つきのメタフィールドを除外
            if (strpos($fd, '@') !== false) {
                // hoge@pathであれば、hogeフィールドに後で代入する
                if (strpos($fd, '@path') !== false) {
                    $atPathAry[] = $fd;
                }
                unset($aryFd[$i]);
            }
        }

        return [
            'aryFd'     => $aryFd,
            'atPathAry' => $atPathAry,
        ];
    }

    /**
     * CSVの組み立て
     *
     * @param string $query
     * @param array $data
     * @return string
     */
    function buildCSV($query, $data)
    {
        $DB     = DB::singleton(dsn());
        $DB->query($query, 'fetch');

        $aryFd      = $data['aryFd'];
        $atPathAry  = $data['atPathAry'];

        $csv = '"' . implode('","', $aryFd) . '"' . "\x0d\x0a";

        while ($row = $DB->fetch($query)) {
            $Field = new Field();
            if (isset($row['log_form_version']) && intval($row['log_form_version']) === 1) {
                $data = acmsDangerUnserialize($row['log_form_data']);
                if ($data instanceof Field) {
                    $Field = $data;
                    $Field->set('log_form_datetime', $row['log_form_datetime']);
                }
            }

            // @path類を処理
            foreach ($atPathAry as $atPath) {
                // $Field->set('fuga', $Field->get('fuga@path'));
                $Field->set(substr($atPath, 0, -5), $Field->get($atPath));
            }

            $csv    .= '"';
            foreach ($aryFd as $i => $fd) {
                $csv    .= (empty($i) ? '' : '","') . str_replace('"', '""', implode(', ', $Field->getArray($fd)));
            }
            $csv    .= '"' . "\x0d\x0a";
        }

        return mb_convert_encoding($csv, $this->Post->get('charset', 'UTF-8'), 'UTF-8');
    }

    /**
     * ダウンロードの実行
     *
     * @param array $csv
     */
    function download($csv)
    {

        header('Content-Length: ' . strlen($csv));
        if (strpos(UA, 'MSIE')) {
            header('Content-Type: text/download');
        } else {
            header('Content-Disposition: attachment');
            header('Content-Type: application/octet-stream');
        }
        die($csv);
    }
}
