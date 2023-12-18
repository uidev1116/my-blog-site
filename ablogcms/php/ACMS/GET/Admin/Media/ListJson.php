<?php

use Acms\Services\Facades\Common;
use Acms\Services\Facades\Media;

class ACMS_GET_Admin_Media_ListJson extends ACMS_GET
{
    /**
     * @var string
     */
    protected $order;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var array
     */
    protected $archiveList;

    /**
     * @var array
     */
    protected $tagList;

    /**
     * @var int
     */
    protected $amount;

    /**
     * @var array
     */
    public $_scope = array(
        'tag' => 'global',
    );

    /**
     * run
     */
    public function get()
    {
        try {
            if (!Media::validate()) {
                throw new \RuntimeException('You are not authorized to upload media.');
            }
            $this->order  = ORDER ? ORDER : 'last_modified-desc';
            $limits = configArray('admin_limit_option');
            $this->limit  = LIMIT ? LIMIT : $limits[config('admin_limit_default')];

            $sql = $this->buildSql();
            $this->archiveList = Media::getMediaArchiveList($sql);
            $this->tagList = Media::getMediaTagList($sql);
            $this->extList = Media::getMediaExtensionList($sql);
            $this->filter($sql);
            $this->amount = $this->getAmount($sql);
            $this->addMediaTagInfo($sql);
            $sql->setLimit($this->limit, (PAGE - 1) * $this->limit);
            ACMS_Filter::mediaOrder($sql, $this->order);
            $q = $sql->get(dsn());

            $json = $this->buildJson($q);
            $json['status'] = 'success';
            Common::responseJson($json);

        } catch (\Exception $e) {
            AcmsLogger::notice('メディア一覧のJSON取得に失敗しました', Common::exceptionArray($e));

            Common::responseJson(array(
                'status' => 'failure',
                'message' => $e->getMessage(),
            ));
        }
        die();
    }

    /**
     * @param string $q
     * @return array
     */
    protected function buildJson($q)
    {
        $json = array(
            'total' => $this->amount,
            'pageAmount' => ceil($this->amount / $this->limit),
            'archives' => $this->archiveList,
            'tags' => $this->tagList,
            'extensions' => $this->extList,
            'largeSize' => intval(config('image_size_large')),
        );
        $db = DB::singleton(dsn());
        $db->query($q, 'fetch');
        $items = array();
        while ($row = $db->fetch($q)) {
            $mid = $row['media_id'];
            $bid = $row['media_blog_id'];
            $data = array(
                'status' => $row['media_status'],
                'path' => $row['media_path'],
                'thumbnail' => $row['media_thumbnail'],
                'name' => $row['media_file_name'],
                'size' => $row['media_image_size'],
                'filesize' => $row['media_file_size'],
                'type' => $row['media_type'],
                'extension' => $row['media_extension'],
                'original' => $row['media_original'],
                'update_date' => $row['media_update_date'],
                'upload_date' => $row['media_upload_date'],
                'field_1' => $row['media_field_1'],
                'field_2' => $row['media_field_2'],
                'field_3' => $row['media_field_3'],
                'field_4' => $row['media_field_4'],
                'field_5' => $row['media_field_5'],
                'field_6' => $row['media_field_6'],
                'blog_name' => $row['blog_name'],
                'editable' => intval($row['media_user_id']) === SUID || sessionWithCompilation()
            );
            $tags = $row['tag_name'];
            $items[] = Media::buildJson($mid, $data, $tags, $bid);
        }
        $json['items'] = $items;

        return $json;
    }

    /**
     * @return \SQL_Select
     */
    protected function buildSql()
    {
        $sql = SQL::newSelect('media');
        $sql->addLeftJoin('blog', 'blog_id', 'media_blog_id');
        ACMS_Filter::blogTree($sql, SBID, 'descendant-or-self');
        if (getAuthConsideringRole(SUID) === 'contributor') {
            $sql->addWhereIn('media_user_id', array(0, SUID));
        }
        return $sql;
    }

    protected function addMediaTagInfo(& $sql)
    {
        $sql->addSelect(' *');
        $sql->addSelect('media_tag_name', 'tag_name', 'media_tag_list', 'GROUP_CONCAT');
        $sql->addLeftJoin('media_tag', 'media_tag_media_id', 'media_id', 'media_tag_list');
        $sql->addGroup('media_id');
    }

    /**
     * @param \SQL_Select $sql
     */
    protected function filter(& $sql)
    {
        if (!empty($this->tags)) {
            Media::filterTag($sql, $this->tags);
        }
        if (KEYWORD) {
            $sql->addWhereOpr('media_file_name', '%'. KEYWORD . '%', 'LIKE');
        }
        if (DATE) {
            $date = str_replace('/', '-', DATE) . '-01';
            $time = strtotime($date);
            $start = date('Y-m-01 00:00:00', $time);
            $end = date('Y-m-t 23:59:59', $time);
            $sql->addWhereBw('media_upload_date', $start, $end);
        } else if ($this->Get->get('year')) {
            $sql->addWhereOpr('YEAR(media_upload_date)', $this->Get->get('year'));
        } else if ($this->Get->get('month')) {
            $sql->addWhereOpr('MONTH(media_upload_date)', $this->Get->get('month'));
        }
        if ($this->Get->get('owner') === 'true') {
            $sql->addWhereOpr('media_user_id', SUID);
        }
        $type = $this->Get->get('type');
        $ext = $this->Get->get('ext');
        if ($type && $type !== 'all') {
            if ($type === 'image') {
                $sql->addWhereIn('media_type', array('image', 'svg'));
            } else {
                $sql->addWhereOpr('media_type', $type);
            }
        }
        if ($ext && $ext !== 'all') {
            $sql->addWhereOpr('media_extension', $ext);
        }
    }

    /**
     * @param \SQL_Select $sql
     * @return int
     */
    protected function getAmount($sql)
    {
        $amount = new SQL_Select($sql);
        $amount->setSelect('DISTINCT(media_id)', 'media_amount', null, 'count');
        return intval(DB::query($amount->get(dsn()), 'one'));
    }
}
