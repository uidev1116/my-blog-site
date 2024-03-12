<?php

class ACMS_POST_Blog_Index_Status extends ACMS_POST_Blog
{
    function post()
    {
        $this->Post->reset(true);
        $status = $this->Post->get('batchStatus');
        $res    = true;
        switch (ACMS_RAM::blogStatus(BID)) {
            case 'open':
                break;
            case 'secret':
                if ('open' == $status) {
                    $res = false;
                }
                break;
            case 'close':
            default:
                $res = false;
        }
        $this->Post->setMethod('blog', 'isOperable', sessionWithAdministration() and !!$res);
        $this->Post->setMethod('batchStatus', 'in', array('open', 'close', 'secret'));
        $this->Post->setMethod('checks', 'required');
        $this->Post->validate(new ACMS_Validator());

        if ($this->Post->isValidAll()) {
            $DB     = DB::singleton(dsn());
            foreach ($this->Post->getArray('checks') as $bid) {
                if (!($bid = idval($bid))) {
                    continue;
                }
                if (
                    !(1
                    and ACMS_RAM::blogLeft(SBID) <= ACMS_RAM::blogRight($bid)
                    and ACMS_RAM::blogRight(SBID) >= ACMS_RAM::blogRight($bid)
                    )
                ) {
                    continue;
                }

                $aryStatus  = array();
                switch ($status) {
                    case 'close':
                        $aryStatus[]    = 'secret';
                        break;
                    case 'secret':
                        $aryStatus[]    = 'open';
                        break;
                    case 'open':
                        break;
                    default:
                        break;
                }

                if (!empty($aryStatus)) {
                    $SQL    = SQL::newSelect('blog');
                    $SQL->setSelect('blog_id');
                    $SQL->addWhereIn('blog_status', $aryStatus);
                    $SQL->addWhereOpr('blog_left', ACMS_RAM::blogLeft($bid), '>');
                    $SQL->addWhereOpr('blog_right', ACMS_RAM::blogRight($bid), '<');
                    $SQL->setLimit(1);
                    if (!!$DB->query($SQL->get(dsn()), 'one')) {
                        continue;
                    }
                }

                $SQL    = SQL::newUpdate('blog');
                $SQL->addUpdate('blog_status', $status);
                $SQL->addWhereOpr('blog_id', $bid);
                $DB->query($SQL->get(dsn()), 'exec');

                Cache::flush('temp');

                $this->Post->set('success', 'status');

                $aryBid[] = $bid;
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

            AcmsLogger::info('指定されたブログのステータスを「' . $status . '」に変更', [
                'targetBIDs' => implode(',', $aryBid),
            ]);
        } else {
            $this->Post->set('error', 'status_1');
        }

        return $this->Post;
    }
}
