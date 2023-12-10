<?php

declare(strict_types=1);

namespace Acms\Services\Shortcut\Entities;

/**
 * ショートカット
 */
class Shortcut implements \JsonSerializable
{
    /**
     * @return array{
     *  bid: int|null,
     *  cid: int|null,
     *  rid: int|null,
     *  mid: int|null,
     *  scid: int|null,
     *  setid: int|null
     * }
     */
    public function getIds(): array
    {
        return [
            'bid' => $this->bid,
            'cid' => $this->cid,
            'rid' => $this->rid,
            'mid' => $this->mid,
            'setid' => $this->setid,
            'scid' => $this->scid
        ];
    }

    /**
     * @return string[]
     */
    public function getDataBaseKeyValues(): array
    {
        return [
            $this->key . '_name' => $this->name,
            $this->key . '_auth' => $this->auth,
            $this->key . '_action' => $this->action
        ];
    }

    /**
     * キー
     *
     * @var string
     */
    private $key;

    /**
     * ソート番号
     *
     * @var int
     */
    private $sort;

    /**
     * 名前
     *
     * @var string
     */
    private $name;

    /**
     * 権限
     *
     * @var string
     */
    private $auth;

    /**
     * アクション（未使用）
     * Ver. 3.0以下の互換性のため残す
     *
     * @var string
     */
    private $action;

    /**
     * 管理ページ
     *
     * @var string
     */
    private $admin;

    /**
     * ブログID
     *
     * @var int|null
     */
    private $bid;

    /**
     * カテゴリーID
     *
     * @var int|null
     */
    private $cid;

    /**
     * ルールID
     *
     * @var int|null
     */
    private $rid;

    /**
     * モジュールID
     *
     * @var int|null
     */
    private $mid;

    /**
     * コンフィグセットID
     *
     * @var int|null
     */
    private $setid;

    /**
     * スケジュールID
     *
     * @var int|null
     */
    private $scid;

    /**
     * 所属ブログID
     *
     * @var int
     */
    private $blogId;

    public function __construct(
        ?string $key,
        ?int $sort = null,
        ?string $name = '',
        ?string $auth = '',
        ?string $action = '',
        ?string $admin = '',
        ?int $bid = null,
        ?int $cid = null,
        ?int $rid = null,
        ?int $mid = null,
        ?int $setid = null,
        ?int $scid = null,
        ?int $blogId = null
    ) {
        $this->key = $key;
        $this->sort = $sort;
        $this->name = $name;
        $this->auth = $auth;
        $this->action = $action;
        $this->admin = $admin;
        $this->bid = $bid;
        $this->cid = $cid;
        $this->rid = $rid;
        $this->mid = $mid;
        $this->setid = $setid;
        $this->scid = $scid;
        $this->blogId = $blogId;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAuth(): string
    {
        return $this->auth;
    }

    public function setAuth(string $auth): void
    {
        $this->auth = $auth;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getAdmin(): string
    {
        return $this->admin;
    }

    public function setAdmin(string $admin): void
    {
        $this->admin = $admin;
    }

    public function getBlogId(): int
    {
        return $this->blogId;
    }

    public function setBlogId(int $blogId): void
    {
        $this->blogId = $blogId;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $json = [];

        if (!empty($this->key)) {
            $json['key'] = $this->key;
        }
        if (!empty($this->sort)) {
            $json['sort'] = $this->sort;
        }
        if (!empty($this->name)) {
            $json['name'] = $this->name;
        }
        if (!empty($this->auth)) {
            $json['auth'] = $this->auth;
        }
        if (!empty($this->action)) {
            $json['action'] = $this->action;
        }
        if (!empty($this->admin)) {
            $json['admin'] = $this->admin;
        }
        if (!empty($this->bid)) {
            $json['bid'] = $this->bid;
        }
        if (!empty($this->cid)) {
            $json['cid'] = $this->cid;
        }
        if (!empty($this->rid)) {
            $json['rid'] = $this->rid;
        }
        if (!empty($this->mid)) {
            $json['mid'] = $this->mid;
        }
        if (!empty($this->setid)) {
            $json['setid'] = $this->setid;
        }
        if (!empty($this->scid)) {
            $json['scid'] = $this->scid;
        }
        if (!empty($this->blogId)) {
            $json['blogId'] = $this->blogId;
        }

        return empty($json) ? new \stdClass() : $json;
    }
}
