<?php

namespace Acms\Services\Unit\Models;

use Acms\Services\Unit\Contracts\Model;
use Acms\Services\Facades\Cache;
use ACMS_Hook;
use Embed\Embed;
use Template;

class Quote extends Model
{
    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    public function getUnitType(): string
    {
        return 'quote';
    }

    /**
     * ユニットが画像タイプか取得
     *
     * @return bool
     */
    public function getIsImageUnit(): bool
    {
        return false;
    }

    /**
     * ユニットのデフォルト値をセット
     *
     * @param string $configKeyPrefix
     * @param int $configIndex
     * @return void
     */
    public function setDefault(string $configKeyPrefix, int $configIndex): void
    {
        $this->setField1(config("{$configKeyPrefix}field_1", '', $configIndex));
        $this->setField2(config("{$configKeyPrefix}field_2", '', $configIndex));
        $this->setField3(config("{$configKeyPrefix}field_3", '', $configIndex));
        $this->setField4(config("{$configKeyPrefix}field_4", '', $configIndex));
        $this->setField5(config("{$configKeyPrefix}field_5", '', $configIndex));
        $this->setField6(config("{$configKeyPrefix}field_6", '', $configIndex));
        $this->setField7(config("{$configKeyPrefix}field_7", '', $configIndex));
    }

    /**
     * POSTデータからユニット独自データを抽出
     *
     * @param array $post
     * @param bool $removeOld
     * @param bool $isDirectEdit
     * @return void
     */
    public function extract(array $post, bool $removeOld = true, bool $isDirectEdit = false): void
    {
        $id = $this->getTempId();
        $quoteUrl = $this->implodeUnitData($_POST["quote_url_{$id}"]);
        if ($isDirectEdit && strlen($quoteUrl) === 0) {
            $quoteUrl = config('action_direct_def_quote_url');
        }
        $this->setField6($quoteUrl);

        $urlAry = $this->explodeUnitData($quoteUrl);
        $field1Ary = [];
        $field2Ary = [];
        $field3Ary = [];
        $field4Ary = [];
        $field5Ary = [];
        $field7Ary = [];

        foreach ($urlAry as $i => $url) {
            if (preg_match(REGEX_VALID_URL, $url)) {
                $cache = Cache::field();
                $cacheKey = md5($url);
                $cacheItem = $cache->getItem($cacheKey);
                if ($cacheItem && $cacheItem->isHit()) {
                    $field = $cacheItem->get();
                    $field1Ary[] = $field['field1'] ?? '';
                    $field2Ary[] = $field['field2'] ?? '';
                    $field3Ary[] = $field['field3'] ?? '';
                    $field4Ary[] = $field['field4'] ?? '';
                    $field5Ary[] = $field['field5'] ?? '';
                    $field7Ary[] = $field['field7'] ?? '';
                } else {
                    $html = '';
                    $field1 = '';
                    $field2 = '';
                    $field3 = '';
                    $field4 = '';
                    $field5 = '';
                    if (HOOK_ENABLE) {
                        $Hook = ACMS_Hook::singleton();
                        $Hook->call('extendsQuoteUnit', [$url, &$html]);
                    }
                    if (is_string($html) && $html !== '') { // @phpstan-ignore-line
                        $field7Ary[] = $html;
                    } else {
                        try {
                            $graph = Embed::create($url);
                            if ($graph) { // @phpstan-ignore-line
                                $field1 = $graph->providerName;
                                $field2 = $graph->authorName;
                                $field3 = $graph->title;
                                $field4 = $graph->description;
                                $field5 = $graph->image;
                            }
                        } catch (\Exception $e) {
                        }
                        $field1Ary[] = $field1;
                        $field2Ary[] = $field2;
                        $field3Ary[] = $field3;
                        $field4Ary[] = $field4;
                        $field5Ary[] = $field5;
                    }
                    $cache->put($cacheKey, [
                        'field1' => $field1,
                        'field2' => $field2,
                        'field3' => $field3,
                        'field4' => $field4,
                        'field5' => $field5,
                        'field7' => $html,
                    ]);
                }
            }
        }

        $this->setField1($this->implodeUnitData($field1Ary));
        $this->setField2($this->implodeUnitData($field2Ary));
        $this->setField3($this->implodeUnitData($field3Ary));
        $this->setField4($this->implodeUnitData($field4Ary));
        $this->setField5($this->implodeUnitData($field5Ary));
        $this->setField7($this->implodeUnitData($field7Ary));
    }

    /**
     * 保存できるユニットか判断
     *
     * @return bool
     */
    public function canSave(): bool
    {
        if (empty($this->getField6())) {
            return false;
        }
        return true;
    }

    /**
     * ユニット複製時の専用処理
     *
     * @return void
     */
    public function handleDuplicate(): void
    {
    }

    /**
     * ユニット削除時の専用処理
     *
     * @return void
     */
    public function handleRemove(): void
    {
    }

    /**
     * キーワード検索用のワードを取得
     *
     * @return string
     */
    public function getSearchText(): string
    {
        return '';
    }

    /**
     * ユニットのサマリーテキストを取得
     *
     * @return string[]
     */
    public function getSummaryText(): array
    {
        return [];
    }

    /**
     * ユニットの描画
     *
     * @param Template $tpl
     * @param array $vars
     * @param string[] $rootBlock
     * @return void
     */
    public function render(Template $tpl, array $vars, array $rootBlock): void
    {
        if (empty($this->getField6())) {
            return;
        }
        $url = $this->getField6();
        $vars += [
            'quote_url' => $url,
        ];
        $this->formatMultiLangUnitData($this->getField6(), $vars, 'quote_url');

        if ($html = $this->getField7()) {
            $vars['quote_html'] = $html;
            $this->formatMultiLangUnitData($html, $vars, 'quote_html');
        }
        if ($siteName = $this->getField1()) {
            $vars['quote_site_name'] = $siteName;
            $this->formatMultiLangUnitData($siteName, $vars, 'quote_site_name');
        }
        if ($author = $this->getField2()) {
            $vars['quote_author'] = $author;
            $this->formatMultiLangUnitData($author, $vars, 'quote_author');
        }
        if ($title = $this->getField3()) {
            $vars['quote_title'] = $title;
            $this->formatMultiLangUnitData($title, $vars, 'quote_title');
        }
        if ($description = $this->getField4()) {
            $vars['quote_description'] = $description;
            $this->formatMultiLangUnitData($description, $vars, 'quote_description');
        }
        if ($image = $this->getField5()) {
            $vars['quote_image'] = $image;
            $this->formatMultiLangUnitData($image, $vars, 'quote_image');
        }
        $vars['align'] = $this->getAlign();
        $vars['attr'] = $this->getAttr() ?: null;

        $tpl->add(['unit#' . $this->getType()], $vars);
    }

    /**
     * 編集画面のユニット描画
     *
     * @param Template $tpl
     * @param array $vars
     * @param string[] $rootBlock
     * @return void
     */
    public function renderEdit(Template $tpl, array $vars, array $rootBlock): void
    {
        $vars += [
            'quote_url' => $this->getField6(),
            'html' => $this->getField7(),
            'site_name' => $this->getField1(),
            'author' => $this->getField2(),
            'title' => $this->getField3(),
            'description' => $this->getField4(),
            'image' => $this->getField5(),
        ];
        $this->formatMultiLangUnitData($vars['quote_url'], $vars, 'quote_url');
        $this->formatMultiLangUnitData($vars['html'], $vars, 'html');
        $this->formatMultiLangUnitData($vars['site_name'], $vars, 'site_name');
        $this->formatMultiLangUnitData($vars['author'], $vars, 'author');
        $this->formatMultiLangUnitData($vars['title'], $vars, 'title');
        $this->formatMultiLangUnitData($vars['description'], $vars, 'description');
        $this->formatMultiLangUnitData($vars['image'], $vars, 'image');

        $tpl->add(array_merge([$this->getUnitType()], $rootBlock), $vars);
    }

    /**
     * レガシーなユニットデータを返却（互換性のため）
     *
     * @return array
     */
    protected function getLegacy(): array
    {
        return [
            'quote_url' => $this->getField6(),
            'html' => $this->getField7(),
            'site_name' => $this->getField1(),
            'author' => $this->getField2(),
            'title' => $this->getField3(),
            'description' => $this->getField4(),
            'image' => $this->getField5(),
        ];
    }
}
