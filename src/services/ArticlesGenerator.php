<?php

namespace app\services;

use app\models\Article;
use app\models\Tag;
use joshtronic\LoremIpsum;

/**
 * Class ArticlesGenerator
 *
 * Generates random articles for testing purposes.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class ArticlesGenerator
{
    /**
     * @var LoremIpsum
     */
    private $ipsum;

    /**
     * ArticlesGenerator constructor.
     *
     * @param LoremIpsum $ipsum
     */
    public function __construct(LoremIpsum $ipsum)
    {
        $this->ipsum = $ipsum;
    }

    /**
     * @param int $number Number of articles to be generated
     * @return Article[]
     */
    public function generate(int $number): array
    {
        $articles = [];
        for ($i = 0; $i < $number; $i++) {
            $articles[] = $this->createRandomArticles();
        }

        return $articles;
    }

    /**
     * @return Article
     */
    private function createRandomArticles(): Article
    {
        $article = new Article([
            'title' => $this->generateRandomTitle(),
            'text' => $this->generateRandomText(),
        ]);
        $article->save();
        $this->generateTagsForArticle($article);

        return $article;
    }

    /**
     * @return Tag
     */
    private function getRandomTag(): Tag
    {
        $tags = [
            'hit',
            'politics',
            'culture',
            'technologies',
            'health',
            'music',
            'cinema',
            'climate',
            'science',
            'nature',
            'photography',
            'biology',
        ];

        $i = mt_rand(0, count($tags) - 1);

        return $this->ensureTag($tags[$i]);
    }

    /**
     * @param string $name
     * @return Tag
     */
    private function ensureTag(string $name): Tag
    {
        if ($tag = Tag::find()->where(['name' => $name])->one()) {
            return $tag;
        }

        $tag = new Tag(['name' => $name]);
        $tag->save();

        return $tag;
    }

    /**
     * @param Article $article
     * @void
     */
    private function generateTagsForArticle($article): void
    {
        $count = mt_rand(1, 5);

        for ($i = 0; $i < $count; $i++) {
            $article->link('tags', $this->getRandomTag());
        }
    }

    /**
     * @return string
     */
    private function generateRandomTitle(): string
    {
        return $this->ipsum->words(8);
    }

    /**
     * @return string
     */
    private function generateRandomText(): string
    {
        return $this->ipsum->paragraphs(2);
    }
}
