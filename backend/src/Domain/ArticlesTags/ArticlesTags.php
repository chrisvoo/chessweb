<?php

namespace App\Domain\ArticlesTags;

use App\Domain\BaseDomainEntity;
use JsonSerializable;

class ArticlesTags extends BaseDomainEntity implements JsonSerializable
{
    public const TABLE_NAME = 'article_tags';

    public int $article_id;
    public int $tag_id;

    public function jsonSerialize(): mixed
    {
        return [
            'article_id' => $this->article_id,
            'tag_id' => $this->tag_id
        ];
    }
}
