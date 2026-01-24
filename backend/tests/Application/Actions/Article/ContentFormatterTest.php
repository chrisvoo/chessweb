<?php

namespace Tests\Application\Actions\Article;

use App\Application\Actions\Article\Formatters\ContentFormatter;
use App\Domain\Article\Article;
use PHPUnit\Framework\TestCase;

class ContentFormatterTest extends TestCase
{
    private ContentFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new ContentFormatter();
    }

    public function testFormatOnSaveReplacesNbspInContent(): void
    {
        $article = new Article();
        $article->title = 'Test Title';
        $article->content = 'Hello&nbsp;World&nbsp;Test';

        $this->formatter->formatOnSave($article);

        $this->assertEquals('Hello World Test', $article->content);
    }

    public function testFormatOnSaveReplacesNbspInTitle(): void
    {
        $article = new Article();
        $article->title = 'Test&nbsp;Title&nbsp;Here';
        $article->content = 'Some content';

        $this->formatter->formatOnSave($article);

        $this->assertEquals('Test Title Here', $article->title);
    }

    public function testFormatOnSaveReplacesMultipleNbsp(): void
    {
        $article = new Article();
        $article->title = 'Title&nbsp;&nbsp;&nbsp;With&nbsp;Spaces';
        $article->content = 'Content&nbsp;&nbsp;With&nbsp;&nbsp;Multiple&nbsp;Spaces';

        $this->formatter->formatOnSave($article);

        $this->assertEquals('Title   With Spaces', $article->title);
        $this->assertEquals('Content  With  Multiple Spaces', $article->content);
    }

    public function testFormatOnSaveNoChangeWhenNoNbsp(): void
    {
        $article = new Article();
        $article->title = 'Normal Title';
        $article->content = 'Normal content without special characters';

        $this->formatter->formatOnSave($article);

        $this->assertEquals('Normal Title', $article->title);
        $this->assertEquals('Normal content without special characters', $article->content);
    }

    public function testFormatOnSaveWithHtmlContent(): void
    {
        $article = new Article();
        $article->title = 'Article&nbsp;Title';
        $article->content = '<p>Paragraph&nbsp;with&nbsp;spaces</p><div>Another&nbsp;div</div>';

        $this->formatter->formatOnSave($article);

        $this->assertEquals('Article Title', $article->title);
        $this->assertEquals('<p>Paragraph with spaces</p><div>Another div</div>', $article->content);
    }
}
