<?php

use Acms\Services\Facades\Media;

class ACMS_GET_Ogp extends ACMS_GET
{
    public $_scope = [
        'uid' => 'global',
        'cid' => 'global',
        'eid' => 'global',
        'keyword' => 'global',
        'tag' => 'global',
        'date' => 'global',
        'page' => 'global',
    ];

    protected $glue = ' | ';

    /**
     * @inheritDoc
     */
    public function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        $this->glue = config('ogp_title_delimiter', ' | ');
        $vars = [
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'keywords' => $this->getKeywords(),
            'type' => $this->getType(),
        ];

        $imageData = $this->getImage();
        if ($imageData) {
            $vars = array_merge($vars, [
                'image' => $imageData['path'],
                'image@x' => $imageData['width'],
                'image@y' => $imageData['height'],
                'image@type' => $imageData['type'],
            ]);
        }

        return $Tpl->render($vars);
    }

    public function getType()
    {
        if (RBID === BID && VIEW === 'top') {
            return 'website';
        } else {
            return 'article';
        }
    }

    /**
     * og:title を取得します
     * @return string
     */
    public function getTitle()
    {
        $config = config('ogp_title_order', 'entry,page,tag,keyword,date,admin,404,category,blog,rootBlog');
        $title = '';
        $parts = preg_split(REGEXP_SEPARATER, $config, -1, PREG_SPLIT_NO_EMPTY);
        $parts = array_unique($parts);

        foreach ($parts as $part) {
            $method = 'get' . ucwords($part) . 'Title';
            if (is_callable([$this, $method])) {
                if ($val = call_user_func([$this, $method])) {
                    $title .= ($val . $this->glue);
                }
            }
        }
        return rtrim($title, $this->glue);
    }

    /**
     * og:image を取得
     * 優先度: EntryField -> EntryMainImage -> CategoryField -> BlogField
     *
     * @return false|array{
     *   type: 'image' | 'media',
     *   width: int,
     *   height: int,
     *   path: string
     * }
     */
    public function getImage()
    {
        if ($image = $this->getEntryImage()) {
            return $image;
        }
        if ($image = $this->getCategoryImage()) {
            return $image;
        }
        if ($image = $this->getBlogImage(BID)) {
            return $image;
        }
        if ($image = $this->getBlogImage(RBID)) {
            return $image;
        }
        return false;
    }

    /**
     * og:description を取得
     * 優先度: EntryField -> EntrySummary -> CategoryField -> BlogField
     *
     * @return bool|string
     */
    public function getDescription()
    {
        $hide_summary = EID && config('ogp_description_hide_summary') === 'true';
        if ($description = $this->getEntryDescription($hide_summary)) {
            return $description;
        }
        if ($description = $this->getCategoryDescription()) {
            return $description;
        }
        if ($description = $this->getBlogDescription(BID)) {
            return $description;
        }
        if ($description = $this->getBlogDescription(RBID)) {
            return $description;
        }
        return false;
    }

    /**
     * keywords を取得
     * 優先度: EntryField -> CategoryField -> BlogField
     *
     * @return bool|string
     */
    public function getKeywords()
    {
        if ($keyword = $this->getEntryKeywords()) {
            return $keyword;
        }
        if ($keyword = $this->getCategoryKeywords()) {
            return $keyword;
        }
        if ($keyword = $this->getBlogKeywords(BID)) {
            return $keyword;
        }
        if ($keyword = $this->getBlogKeywords(RBID)) {
            return $keyword;
        }
        return false;
    }

    protected function getSize($str, $type = 'width')
    {
        if (preg_match('/[^x]+x[^x]+/', $str)) {
            list($x, $y) = explode('x', $str);
            if ($type === 'width') {
                return intval(trim($x));
            }
            return intval(trim($y));
        }
        return '';
    }

    /**
     * @return false|array{
     *   type: 'image' | 'media',
     *   width: int,
     *   height: int,
     *   path: string
     * }
     */
    protected function getEntryImage()
    {
        if (!EID) {
            return false;
        }
        $field_name = config('ogp_image_entry_field_name', false);
        if (!empty($field_name)) {
            $field = loadEntryField(EID);
            if ($mediaId = $field->get($field_name . '@media')) {
                if ($media = Media::getMedia($mediaId)) {
                    if ($media['type'] === 'image' || $media['type'] === 'svg') {
                        return [
                            'type' => 'media',
                            'width' => $this->getSize($media['size'], 'width'),
                            'height' => $this->getSize($media['size'], 'height'),
                            'path' => $media['path'],
                        ];
                    }
                }
            }
            if ($image = $field->get($field_name . '@path')) {
                list($width, $height) = Storage::getImageSize(ARCHIVES_DIR . $image);
                return [
                    'type' => 'image',
                    'width' => $width,
                    'height' => $height,
                    'path' => $image,
                ];
            }
        }
        if (config('ogp_image_unit_image_not_use') === 'true') {
            return false;
        }
        if ($primary_img_id = ACMS_RAM::entryPrimaryImage(EID)) {
            if ($unit = ACMS_RAM::unit($primary_img_id)) {
                if ($unit['column_align'] === 'hidden') {
                    return false;
                }
                $type = detectUnitTypeSpecifier($unit['column_type']);
                if ($type === 'media') {
                    if ($media = Media::getMedia($unit['column_field_1'])) {
                        if ($media['type'] === 'image' || $media['type'] === 'svg') {
                            return [
                                'type' => 'media',
                                'width' => $this->getSize($media['size'], 'width'),
                                'height' => $this->getSize($media['size'], 'height'),
                                'path' => $media['path'],
                            ];
                        }
                    }
                } else {
                    list($width, $height) = Storage::getImageSize(ARCHIVES_DIR . $unit['column_field_2']);
                    return [
                        'type' => 'image',
                        'width' => $width,
                        'height' => $height,
                        'path' => $unit['column_field_2'],
                    ];
                }
            }
        }
        return false;
    }

    /**
     * @return false|array{
     *   type: 'image' | 'media',
     *   width: int,
     *   height: int,
     *   path: string
     * }
     */
    protected function getCategoryImage()
    {
        if (!CID) {
            return false;
        }
        $field_name = config('ogp_image_category_field_name', false);
        if (!empty($field_name)) {
            $field = loadCategoryField(CID);
            if ($mediaId = $field->get($field_name . '@media')) {
                if ($media = Media::getMedia($mediaId)) {
                    if ($media['type'] === 'image' || $media['type'] === 'svg') {
                        return [
                            'type' => 'media',
                            'width' => $this->getSize($media['size'], 'width'),
                            'height' => $this->getSize($media['size'], 'height'),
                            'path' => $media['path'],
                        ];
                    }
                }
            }
            if ($image = $field->get($field_name . '@path')) {
                list($width, $height) = Storage::getImageSize(ARCHIVES_DIR . $image);
                return [
                    'type' => 'image',
                    'width' => $width,
                    'height' => $height,
                    'path' => $image,
                ];
            }
        }
        return false;
    }

    /**
     * @param int $bid
     * @return false|array{
     *   type: 'image' | 'media',
     *   width: int,
     *   height: int,
     *   path: string
     * }
     */
    protected function getBlogImage($bid = BID)
    {
        if (empty($bid)) {
            return false;
        }
        $field_name = config('ogp_image_blog_field_name', false);
        if (!empty($field_name)) {
            $field = loadBlogField($bid);
            if ($mediaId = $field->get($field_name . '@media')) {
                if ($media = Media::getMedia($mediaId)) {
                    if ($media['type'] === 'image' || $media['type'] === 'svg') {
                        return [
                            'type' => 'media',
                            'width' => $this->getSize($media['size'], 'width'),
                            'height' => $this->getSize($media['size'], 'height'),
                            'path' => $media['path'],
                        ];
                    }
                }
            }
            if ($image = $field->get($field_name . '@path')) {
                list($width, $height) = Storage::getImageSize(ARCHIVES_DIR . $image);
                return [
                    'type' => 'image',
                    'width' => $width,
                    'height' => $height,
                    'path' => $image,
                ];
            }
        }
        return false;
    }

    /**
     * @param bool $hide
     * @return bool|string
     */
    protected function getEntryDescription($hide = false)
    {
        if (!EID) {
            return false;
        }
        $field_name = config('ogp_description_entry_field_name', false);
        if (!empty($field_name)) {
            $field = loadEntryField(EID);
            if ($description = $field->get($field_name)) {
                return $description;
            }
        }
        if ($hide) {
            return false;
        }
        $vars = [];
        if ($vars = Tpl::buildSummaryFulltext($vars, EID, Tpl::eagerLoadFullText([EID]))) {
            if (isset($vars['summary'])) {
                return $vars['summary'];
            }
        }
        return false;
    }

    /**
     * @return bool|string
     */
    protected function getCategoryDescription()
    {
        if (!CID) {
            return false;
        }
        $field_name = config('ogp_description_category_field_name', false);
        if (!empty($field_name)) {
            $field = loadCategoryField(CID);
            if ($description = $field->get($field_name)) {
                return $description;
            }
        }
        return false;
    }

    /**
     * @param int $bid
     * @return bool|string
     */
    protected function getBlogDescription($bid = BID)
    {
        if (empty($bid)) {
            return false;
        }
        $field_name = config('ogp_description_blog_field_name', false);
        if (!empty($field_name)) {
            $field = loadBlogField($bid);
            if ($description = $field->get($field_name)) {
                return $description;
            }
        }
        return false;
    }

    /**
     * @return bool|string
     */
    protected function getEntryKeywords()
    {
        if (!EID) {
            return false;
        }
        $field_name = config('ogp_keywords_entry_field_name', false);
        if (!empty($field_name)) {
            $field = loadEntryField(EID);
            if ($keywords = $field->get($field_name)) {
                return $keywords;
            }
        }
        return false;
    }

    /**
     * @return bool|string
     */
    protected function getCategoryKeywords()
    {
        if (!CID) {
            return false;
        }
        $field_name = config('ogp_keywords_category_field_name', false);
        if (!empty($field_name)) {
            $field = loadCategoryField(CID);
            if ($keywords = $field->get($field_name)) {
                return $keywords;
            }
        }
        return false;
    }

    /**
     * @param int $bid
     * @return bool|string
     */
    protected function getBlogKeywords($bid = BID)
    {
        if (empty($bid)) {
            return false;
        }
        $field_name = config('ogp_keywords_blog_field_name', false);
        if (!empty($field_name)) {
            $field = loadBlogField($bid);
            if ($keywords = $field->get($field_name)) {
                return $keywords;
            }
        }
        return false;
    }

    /**
     * @return bool|string
     */
    protected function getEntryTitle()
    {
        if (!EID) {
            return false;
        }
        if (config('ogp_title_entry_code_empty') === 'on' && ACMS_RAM::entryCode(EID) === '') {
            return false;
        }
        $field_name = config('ogp_title_entry_field_name', false);
        if (!empty($field_name)) {
            $field = loadEntryField(EID);
            if ($title = $field->get($field_name)) {
                return $title;
            }
        }
        return ACMS_RAM::entryTitle(EID);
    }

    /**
     * @return bool|string
     */
    protected function getCategoryTitle()
    {
        if (!CID) {
            return false;
        }
        $field_name = config('ogp_title_category_field_name', false);
        $level = config('ogp_title_category_levels') === 'on';

        $sql = SQL::newSelect('category');
        ACMS_Filter::categoryStatus($sql);
        if ($level) {
            // 階層表示
            ACMS_Filter::categoryTree($sql, CID, 'self-ancestor');
            $sql->addOrder('category_right', 'asc');
        } else {
            // 一件表示
            $sql->addWhereOpr('category_id', CID);
        }
        if (!empty($field_name)) {
            $field = SQL::newSelect('field');
            $field->addSelect('field_cid');
            $field->addSelect('field_value');
            $field->addWhereOpr('field_key', $field_name);
            $sql->addLeftJoin($field, 'field_cid', 'category_id', 'field');
        }
        $all = DB::query($sql->get(dsn()), 'all');

        $title = [];
        foreach ($all as $category) {
            if (isset($category['field_value']) && $category['field_value']) {
                $title[] = $category['field_value'];
                continue;
            }
            $title[] = $category['category_name'];
        }
        return implode($this->glue, $title);
    }

    /**
     * @param int|null $bid
     * @return bool|string
     */
    protected function getBlogTitle($bid = BID)
    {
        if (empty($bid)) {
            return false;
        }
        $field_name = config('ogp_title_blog_field_name', false);
        $level = config('ogp_title_blog_levels') === 'on';

        $sql = SQL::newSelect('blog');
        ACMS_Filter::blogStatus($sql);
        if ($level) {
            // 階層表示
            ACMS_Filter::blogTree($sql, $bid, 'self-ancestor');
            $sql->addOrder('blog_right', 'asc');
        } else {
            // 一件表示
            $sql->addWhereOpr('blog_id', $bid);
        }
        if ($bid !== RBID) {
            $sql->addWhereOpr('blog_id', RBID, '<>');
        }
        if (!empty($field_name)) {
            $field = SQL::newSelect('field');
            $field->addSelect('field_bid');
            $field->addSelect('field_value');
            $field->addWhereOpr('field_key', $field_name);
            $sql->addLeftJoin($field, 'field_bid', 'blog_id', 'field');
        }
        $all = DB::query($sql->get(dsn()), 'all');
        $title = [];
        foreach ($all as $blog) {
            if (isset($blog['field_value']) && $blog['field_value']) {
                $title[] = $blog['field_value'];
                continue;
            }
            $title[] = $blog['blog_name'];
        }
        return implode($this->glue, $title);
    }

    /**
     * @return bool|string
     */
    protected function getRootBlogTitle()
    {
        $root = loadAncestorBlog('root', 'id');
        if (empty($root) || $root == BID) {
            return false;
        }
        return $this->getBlogTitle(intval($root));
    }

    /**
     * @return bool|string
     */
    protected function getPageTitle()
    {
        if (!PAGE || PAGE < 2) {
            return false;
        }
        $suffix = config('ogp_title_page_suffix', '');
        return str_replace('{page}', strval(PAGE), $suffix);
    }

    /**
     * @return bool|string
     */
    protected function getTagTitle()
    {
        if (!TAG) {
            return false;
        }
        $string = '';
        $glue = config('ogp_title_tag_delimiter', '/');
        $tags = Common::getTagsFromString(TAG);
        foreach ($tags as $i => $tag) {
            if ($i > 0) {
                $string .= $glue;
            }
            $string .= $tag;
        }
        return $string;
    }

    /**
     * @return bool|null|string
     */
    protected function getKeywordTitle()
    {
        if (!KEYWORD) {
            return false;
        }
        return KEYWORD;
    }

    /**
     * @return bool|false|string
     */
    protected function getDateTitle()
    {
        if (!DATE) {
            return false;
        }
        if (preg_match('/^\d{4}(\/\d{2})?$/', DATE)) {
            return DATE;
        }
        $format = config('ogp_title_date_format', 'Y-m-d');
        return date($format, strtotime(str_replace('/', '-', DATE)));
    }

    /**
     * @return bool|string
     */
    protected function getAdminTitle()
    {
        if (!ADMIN) {
            return false;
        }
        return config('ogp_title_admin_label', 'Admin Page');
    }

    /**
     * @return bool|string
     */
    protected function get404Title()
    {
        if (defined('ROOT_TPL_NAME') && ROOT_TPL_NAME === '404') {
            return config('ogp_title_404_label', '404 Not Found');
        }
        return false;
    }
}
