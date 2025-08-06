<?php

namespace App\Application\Actions\Article\Formatters;

use App\Domain\Article\Article;

class ContentFormatter
{
    public function formatOnSave(Article $article): void
    {
        // The HTML editor translates all spaces with an HTML entity
        $article->content = str_replace('&nbsp;', ' ', $article->content);
        $article->title = str_replace('&nbsp;', ' ', $article->title);
    }
}
