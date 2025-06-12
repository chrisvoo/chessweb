<?php

namespace App\Domain\Article;

use App\Domain\BaseDomainEntity;
use App\Domain\Category\Category;
use App\Domain\Tag\Tag;
use JsonSerializable;

class Article extends BaseDomainEntity implements JsonSerializable
{
    public const TABLE_NAME = 'articles';

    public int $id;
    public int $author_id;
    public string $title;
    public string $content;

    /** @var Category[] the categories that the article belongs to */
    public array $categories = [];
    /** @var Tag[] the tags that the article belongs to */
    public array $tags = [];
    public string $created_at;

    public ?string $updated_at;
    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): mixed
    {
        $article = [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content ?? '',
            'author_id' => $this->author_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];

        if (!empty($this->tags)) {
            $article['tags'] = $this->tags;
        }

        if (!empty($this->categories)) {
            $article['categories'] = $this->categories;
        }

        return $article;
    }

    public static function getSortableFields(): array
    {
       return [
           'id',
           'title',
           'author_id',
           'created_at'
       ];
    }
}
