<?php

class ACMS_POST_Category_Index_Status extends ACMS_POST
{
    function post()
    {
        $aryCid = $this->Post->getArray('checks');
        $status = $this->Post->get('status');

        $this->Post->reset(true);
        $this->Post->setMethod('category', 'operable', ( 1
            and sessionWithCompilation()
            and !empty($aryCid)
            and in_array($status, array('open', 'close', 'secret'))
        ));
        $this->Post->validate();

        if ($this->Post->isValidAll()) {
            $DB = DB::singleton(dsn());
            $targetCIDs = [];
            while (!!($cid = intval(array_shift($aryCid)))) {
                if (!!$status && $status !== 'open') {
                    // cid collection
                    $SQL    = SQL::newSelect('category');
                    $SQL->setSelect('category_id');
                    $SQL->addWhereOpr('category_blog_id', BID);
                    $SQL->addWhereOpr('category_left', ACMS_RAM::categoryLeft($cid), '>=');
                    $SQL->addWhereOpr('category_right', ACMS_RAM::categoryRight($cid), '<=');

                    if (!!($all = $DB->query($SQL->get(dsn()), 'all'))) {
                        $_aryCid = array();
                        foreach ($all as $row) {
                            if (!($_cid = intval($row['category_id']))) {
                                continue;
                            }
                            if (!is_bool($key = array_search($_cid, $aryCid))) {
                                unset($aryCid[$key]);
                            }
                            $_aryCid[]  = $_cid;
                        }
                        // catetory
                        $SQL    = SQL::newUpdate('category');
                        $SQL->setUpdate('category_status', $status);
                        $SQL->addWhereIn('category_id', $_aryCid);
                        $DB->query($SQL->get(dsn()), 'exec');
                    }
                } else {
                    // check parent status
                    $SQL    = SQL::newSelect('category');
                    $SQL->setSelect('category_id');
                    $SQL->addWhereOpr('category_blog_id', BID);
                    $SQL->addWhereOpr('category_left', ACMS_RAM::categoryLeft($cid), '<');
                    $SQL->addWhereOpr('category_right', ACMS_RAM::categoryRight($cid), '>');
                    $SQL->addWhereOpr('category_status', 'close');
                    $SQL->setLimit(1);
                    if ($DB->query($SQL->get(dsn()), 'one')) {
                        continue;
                    }
                    // update
                    $SQL    = SQL::newUpdate('category');
                    $SQL->addUpdate('category_status', $status);
                    $SQL->addWhereOpr('category_id', $cid);
                    $SQL->addWhereOpr('category_blog_id', BID);
                    $DB->query($SQL->get(dsn()), 'exec');
                }
                $targetCIDs[] = $cid;
            }
            if ($status === 'open') {
                $status = '公開';
            }
            if ($status === 'close') {
                $status = '非公開';
            }
            if ($status === 'secret') {
                $status = 'シークレット';
            }
            AcmsLogger::info('指定されたカテゴリーのステータスを「' . $status . '」に変更', [
                'targetCIDs' => implode(',', $targetCIDs),
            ]);
        } else {
        }
        Cache::flush('temp');

        return $this->Post;

/*
        if ( !sessionWithAdministration() ) die();
        if ( !(($status = ite($_POST, 'status')) and in_array($status, array('open', 'close'))) ) die();
        if ( !empty($_POST['checks']) and is_array($_POST['checks']) ) {
            $DB = DB::singleton(dsn());
            foreach ( $_POST['checks'] as $cid ) {
                if ( !$cid = idval($cid) ) continue;

                if ( 'close' == $status ) {
                    $SQL    = SQL::newUpdate('category');
                    $SQL->setUpdate('category_status', 'close');
                    $SQL->addWhereOpr('category_blog_id', BID);
                    $SQL->addWhereOpr('category_left', ACMS_RAM::categoryLeft($cid), '>');
                    $SQL->addWhereOpr('category_right', ACMS_RAM::categoryRight($cid), '<');
                    $DB->query($SQL->get(dsn()), 'exec');
                } else {
                    $SQL    = SQL::newSelect('category');
                    $SQL->setSelect('category_id');
                    $SQL->addWhereOpr('category_blog_id', BID);
                    $SQL->addWhereOpr('category_left', ACMS_RAM::categoryLeft($cid), '<');
                    $SQL->addWhereOpr('category_right', ACMS_RAM::categoryRight($cid), '>');
                    $SQL->addWhereOpr('category_status', 'close');
                    $SQL->setLimit(1);
                    if ( $DB->query($SQL->get(dsn()), 'one') ) continue;
                }

                $SQL    = SQL::newUpdate('category');
                $SQL->addUpdate('category_status', $status);
                $SQL->addWhereOpr('category_id', $cid);
                $SQL->addWhereOpr('category_blog_id', BID);
                $DB->query($SQL->get(dsn()), 'exec');
            }
        }
        return $this->Post;
*/
    }
}
