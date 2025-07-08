<?php

namespace App\Domain\ArticlesCategories;

use App\Domain\BaseDomainEntity;
use JsonSerializable;

class ArticlesCategories extends BaseDomainEntity implements JsonSerializable
{
    public const TABLE_NAME = 'article_categories';

    public int $article_id;
    public int $category_id;

    public function jsonSerialize(): mixed
    {
        return [
            'article_id' => $this->article_id,
            'tag_id' => $this->category_id
        ];
    }
}
