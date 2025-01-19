<?php

namespace Acms\Services\Unit\Contracts;

use Template;

abstract class Model
{
    use \Acms\Traits\Unit\UnitModelTrait;

    /**
     * ユニットID
     * @var int|null
     */
    private $id;

    /**
     * エントリーID
     * @var int|null
     */
    private $entryId;

    /**
     * revision id
     * @var int|null
     */
    private $revId;

    /**
     * ブログID
     * @var int|null
     */
    private $blogId;

    /**
     * sort
     * @var int
     */
    private $sort = 1;

    /**
     * 配置
     * @var string
     */
    private $align = '';

    /**
     * タイプ
     * @var string
     */
    private $type = '';

    /**
     * 属性
     * @var string
     */
    private $attr = '';

    /**
     * グループ
     * @var string
     */
    private $group = '';

    /**
     * サイズ
     * @var string
     */
    private $size = '';

    /**
     * フィールド1
     * @var string
     */
    private $field1 = '';

    /**
     * フィールド2
     * @var string
     */
    private $field2 = '';

    /**
     * フィールド3
     * @var string
     */
    private $field3 = '';

    /**
     * フィールド4
     * @var string
     */
    private $field4 = '';

    /**
     * フィールド5
     * @var string
     */
    private $field5 = '';

    /**
     * フィールド6
     * @var string
     */
    private $field6 = '';

    /**
     * フィールド7
     * @var string
     */
    private $field7 = '';

    /**
     * フィールド8
     * @var string
     */
    private $field8 = '';

    /**
     * Eager Load されたメディアデータ
     * @var array
     */
    private $eagerLoadedMedia = [];

    /**
     * テンプレートのinputを識別するための一時ID
     * @var string
     */
    private $tempId = '';

    /**
     * 編集アクション
     * @var string
     */
    private $editAction = '';

    /**
     * メイン画像ユニットID
     * @var int|null
     */
    private $primaryImageUnitId = null;

    /**
     * コンストラクター
     */
    public function __construct()
    {
    }

    /**
     * ユニットタイプを取得
     *
     * @return string
     */
    abstract public function getUnitType(): string;

    /**
     * ユニットが画像タイプか取得
     *
     * @return bool
     */
    abstract public function getIsImageUnit(): bool;

    /**
     * ユニットのデフォルト値をセット
     *
     * @param string $configKeyPrefix
     * @param int $configIndex
     * @return void
     */
    abstract public function setDefault(string $configKeyPrefix, int $configIndex): void;

    /**
     * POSTデータからユニット独自データを抽出
     *
     * @param array $post
     * @param bool $removeOld
     * @param bool $isDirectEdit
     * @return void
     */
    abstract public function extract(array $post, bool $removeOld = true, bool $isDirectEdit = false): void;

    /**
     * 保存できるユニットか判断
     *
     * @return bool
     */
    abstract public function canSave(): bool;

    /**
     * ユニット複製時の専用処理
     *
     * @return void
     */
    abstract public function handleDuplicate(): void;

    /**
     * ユニット削除時の専用処理
     *
     * @return void
     */
    abstract public function handleRemove(): void;

    /**
     * キーワード検索用のワードを取得
     *
     * @return string
     */
    abstract public function getSearchText(): string;

    /**
     * ユニットのサマリーテキストを取得
     *
     * @return string[]
     */
    abstract public function getSummaryText(): array;

    /**
     * ユニット描画
     *
     * @param Template $tpl
     * @param array $vars
     * @param string[] $rootBlock
     * @return void
     */
    abstract public function render(Template $tpl, array $vars, array $rootBlock): void;

    /**
     * 編集画面のユニット描画
     *
     * @param Template $tpl
     * @param array $vars
     * @param string[] $rootBlock
     * @return void
     */
    abstract public function renderEdit(Template $tpl, array $vars, array $rootBlock): void;

    /**
     * レガシーなユニットデータを返却（互換性のため）
     *
     * @return array
     */
    abstract protected function getLegacy(): array;

    /**
     * id getter
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * id setter
     *
     * @param int $id
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * entry id getter
     *
     * @return int|null
     */
    public function getEntryId(): ?int
    {
        return $this->entryId;
    }

    /**
     * entry id setter
     *
     * @param int $eid
     * @return void
     */
    public function setEntryId(int $eid): void
    {
        $this->entryId = $eid;
    }

    /**
     * revision id getter
     *
     * @return int|null
     */
    public function getRevId(): ?int
    {
        return $this->revId;
    }

    /**
     * revision id setter
     *
     * @param int $revId
     * @return void
     */
    public function setRevId(int $revId): void
    {
        $this->revId = $revId;
    }

    /**
     * blog id getter
     *
     * @return int|null
     */
    public function getBlogId(): ?int
    {
        return $this->blogId;
    }

    /**
     * blog id setter
     *
     * @param int $bid
     * @return void
     */
    public function setBlogId(int $bid): void
    {
        $this->blogId = $bid;
    }

    /**
     * sort getter
     *
     * @return int
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * sort setter
     *
     * @param int $sort
     * @return void
     */
    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * align getter
     *
     * @return string
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * align setter
     *
     * @param string $align
     * @return void
     */
    public function setAlign(string $align): void
    {
        $this->align = $align;
    }

    /**
     * type getter
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * type setter
     *
     * @param string $type
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * attr getter
     *
     * @return string
     */
    public function getAttr(): string
    {
        return $this->attr;
    }

    /**
     * attr setter
     *
     * @param string $attr
     * @return void
     */
    public function setAttr(string $attr): void
    {
        $this->attr = $attr;
    }

    /**
     * group getter
     *
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * group setter
     *
     * @param string $group
     * @return void
     */
    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    /**
     * size getter
     *
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * size setter
     *
     * @param string $size
     * @return void
     */
    public function setSize(string $size): void
    {
        $this->size = $size;
    }

    /**
     * filed1 getter
     *
     * @return string
     */
    public function getField1(): string
    {
        return $this->field1;
    }

    /**
     * field1 setter
     *
     * @param string $field
     * @return void
     */
    public function setField1(string $field): void
    {
        $this->field1 = $field;
    }

    /**
     * field2 getter
     *
     * @return string
     */
    public function getField2(): string
    {
        return $this->field2;
    }

    /**
     * field2 setter
     *
     * @param string $field
     * @return void
     */
    public function setField2(string $field): void
    {
        $this->field2 = $field;
    }

    /**
     * field3 getter
     *
     * @return string
     */
    public function getField3(): string
    {
        return $this->field3;
    }

    /**
     * field3 setter
     *
     * @param string $field
     * @return void
     */
    public function setField3(string $field): void
    {
        $this->field3 = $field;
    }

    /**
     * field4 getter
     *
     * @return string
     */
    public function getField4(): string
    {
        return $this->field4;
    }

    /**
     * field4 setter
     *
     * @param string $field
     * @return void
     */
    public function setField4(string $field): void
    {
        $this->field4 = $field;
    }

    /**
     * field5 getter
     *
     * @return string
     */
    public function getField5(): string
    {
        return $this->field5;
    }

    /**
     * field5 setter
     *
     * @param string $field
     * @return void
     */
    public function setField5(string $field): void
    {
        $this->field5 = $field;
    }

    /**
     * field6 getter
     *
     * @return string
     */
    public function getField6(): string
    {
        return $this->field6;
    }

    /**
     * field6 setter
     *
     * @param string $field
     * @return void
     */
    public function setField6(string $field): void
    {
        $this->field6 = $field;
    }

    /**
     * field7 getter
     *
     * @return string
     */
    public function getField7(): string
    {
        return $this->field7;
    }

    /**
     * field7 setter
     *
     * @param string $field
     * @return void
     */
    public function setField7(string $field): void
    {
        $this->field7 = $field;
    }

    /**
     * field8 getter
     *
     * @return string
     */
    public function getField8(): string
    {
        return $this->field8;
    }

    /**
     * field8 setter
     *
     * @param string $field
     * @return void
     */
    public function setField8(string $field): void
    {
        $this->field8 = $field;
    }

    /**
     * 一時ID getter
     *
     * @return string
     */
    public function getTempId(): string
    {
        return $this->tempId;
    }

    /**
     * 一時ID setter
     *
     * @param string $tempId
     * @return void
     */
    public function setTempId(string $tempId): void
    {
        $this->tempId = $tempId;
    }

    /**
     * edit action getter
     *
     * @return string
     */
    public function getEditAction(): string
    {
        return $this->editAction;
    }

    /**
     * edit action setter
     *
     * @param string $editAction
     * @return void
     */
    public function setEditAction(string $editAction): void
    {
        $this->editAction = $editAction;
    }

    /**
     * eager loaded media getter
     *
     * @return array
     */
    public function getEagerLoadedMedia(): array
    {
        return $this->eagerLoadedMedia;
    }

    /**
     * eager loaded media setter
     *
     * @param array $media
     * @return void
     */
    public function setEagerLoadedMedia(array $media): void
    {
        $this->eagerLoadedMedia = $media;
    }

    /**
     * primary image unit id getter
     *
     * @return int|null
     */
    public function getPrimaryImageUnitId(): ?int
    {
        return $this->primaryImageUnitId;
    }

    /**
     * primary image unit id setter
     *
     * @param int|null $unitId
     * @return void
     */
    public function setPrimaryImageUnitId(?int $unitId): void
    {
        $this->primaryImageUnitId = $unitId;
    }

    /**
     * 追加時の新規ユニットモデルを作成
     *
     * @param string $addType
     * @param int $configIndex
     * @return void
     */
    public function create(string $addType, int $configIndex): void
    {
        $this->setTempId(uniqueString());
        $this->setAlign(config('column_def_add_' . $addType . '_align', '', $configIndex));
        $this->setGroup(config('column_def_add_' . $addType . '_group', '', $configIndex));
        $this->setAttr(config('column_def_add_' . $addType . '_attr', '', $configIndex));
        $this->setSize(config('column_def_add_' . $addType . '_size', '', $configIndex));
        $this->setEditAction(config('column_def_add_' . $addType . '_edit', '', $configIndex));
        $this->setDefault($this->getUnitDefaultConfigKeyPrefix('add', $addType), $configIndex);
    }

    /**
     * 初期表示時の新規ユニットモデルを作成
     *
     * @param int $configIndex
     * @return void
     */
    public function createDefault(int $configIndex): void
    {
        $this->setTempId(uniqueString());
        $this->setSort($configIndex + 1);
        $this->setAlign(config('column_def_insert_align', 'auto', $configIndex));
        $this->setGroup(config('column_def_insert_group', '', $configIndex));
        $this->setAttr(config('column_def_insert_attr', '', $configIndex));
        $this->setSize(config('column_def_insert_size', '', $configIndex));
        $this->setEditAction(config('column_def_insert_edit', '', $configIndex));
        $this->setDefault($this->getUnitDefaultConfigKeyPrefix('insert', $this->getUnitType()), $configIndex);
    }

    /**
     * ユニットをロード
     *
     * @param array $record
     * @return void
     */
    public function load(array $record)
    {
        $this->id = (int) $record['column_id'];
        $this->revId = (int) ($record['column_rev_id'] ?? 0);
        $this->entryId = (int) $record['column_entry_id'];
        $this->blogId = (int) $record['column_blog_id'];
        $this->sort = (int) $record['column_sort'];
        $this->align = $record['column_align'];
        $this->type = $record['column_type'];
        $this->attr = $record['column_attr'];
        $this->group = $record['column_group'];
        $this->size = $record['column_size'];
        $this->field1 = $record['column_field_1'];
        $this->field2 = $record['column_field_2'];
        $this->field3 = $record['column_field_3'];
        $this->field4 = $record['column_field_4'];
        $this->field5 = $record['column_field_5'];
        $this->field6 = $record['column_field_6'];
        $this->field7 = $record['column_field_7'];
        $this->field8 = $record['column_field_8'];
    }

    /**
     * ユニットを保存してユニットIDを返却
     *
     * @param int $eid
     * @param int $bid
     * @param bool $isRevision
     * @param int|null $rvid
     * @param int $offset
     * @return int
     */
    public function save(int $eid, int $bid, bool $isRevision, ?int $rvid, int $offset): int
    {
        $unitId = $this->getId();
        if (empty($unitId)) {
            // 新規ユニットIDを発行
            $unitId = $this->generateNewIdTrait();
            $this->setId($unitId);
        }
        // ソートを更新
        $sort = $this->getSort() - $offset;
        $this->updateSortNumberTrait($sort, $eid, $bid, $isRevision, $rvid);
        // データセット
        $this->setSort($sort);
        $this->setEntryId($eid);
        $this->setBlogId($bid);
        if ($isRevision && $rvid) {
            $this->setRevId($rvid);
        }
        // ユニットを保存
        $this->insertDataTrait($this, $isRevision);

        return $unitId;
    }

    /**
     * ユニットのデータを結合する
     *
     * @param string[]|string $data
     * @return string
     */
    public function implodeUnitData($data)
    {
        if (is_array($data)) {
            $data = str_replace(':acms_unit_delimiter:', ':acms-unit-delimiter:', $data);
            $data = implode(':acms_unit_delimiter:', $data);
        }
        if (preg_match('/^(:acms_unit_delimiter:)+$/', $data)) {
            $data = '';
        }
        return $data;
    }

    /**
     * ユニットのデータを分割する
     *
     * @param mixed $data
     * @return array
     */
    public function explodeUnitData($data): array
    {
        if (is_string($data)) {
            $data = explode(':acms_unit_delimiter:', $data);
        }
        if (is_array($data)) {
            return $data;
        }
        return [$data];
    }

    /**
     * ユニットのデータを多言語ユニットを考慮して整形する
     *
     * @param mixed $data
     * @param array &$vars
     * @param string $name
     */
    public function formatMultiLangUnitData($data, &$vars = [], $name = '')
    {
        $dataAry = $this->explodeUnitData($data);
        foreach ($dataAry as $u => $var) {
            $var = str_replace(':acms-unit-delimiter:', ':acms_unit_delimiter:', $var);
            $suffix = (string) ($u === 0 ? '' : $u + 1);
            $vars["{$name}{$suffix}"] = $var;
            $vars["{$name}{$suffix}:checked#{$var}"] = config('attr_checked');
            $vars["{$name}{$suffix}:selected#{$var}"] = config('attr_selected');
        }
    }

    /**
     * レガシーなユニットデータを取得（互換性のため）
     * レガシーな方法なため新しく使用はしないでください。
     *
     * @return array
     */
    public function getLegacyData(): array
    {
        $data = [
            'clid' => $this->getId(),
            'type' => $this->getType(),
            'align' => $this->getAlign(),
            'sort' => $this->getSort(),
            'group' => $this->getGroup(),
            'attr' => $this->getAttr(),
            'size' => $this->getSize(),
        ];
        $data += $this->getLegacy();

        return $data;
    }

    /**
     * ユニットのデフォルト値のコンフィグキープレフィックスを取得
     *
     * @param 'add'|'init'|'insert' $mode
     * @param string $addType
     * @return string
     */
    protected function getUnitDefaultConfigKeyPrefix(string $mode, string $addType): string
    {
        if ($mode === 'add') {
            return "column_def_add_{$addType}_";
        }
        return 'column_def_insert_';
    }
}
