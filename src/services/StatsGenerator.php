<?php

namespace app\services;

use app\models\Article;
use app\models\ArticlesStats;

/**
 * Class StatsGenerator
 *
 * Generates [[ArticlesStats]] out of existing articles.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class StatsGenerator
{
    /**
     * @return ArticlesStats
     */
    public function generate(): ArticlesStats
    {
        return new ArticlesStats($this->countPerWord(), $this->articlesCount(), $this->countPerTag());
    }

    /**
     * Counts words stats
     *
     * @return array
     */
    private function countPerWord(): array
    {
        /** @var Article[] $articles */
        $articles = Article::find()->all();
        $wordsCount = [];

        foreach ($articles as $article) {
            $this->countWordsInArticle($article, $wordsCount);
        }

        return $wordsCount;
    }

    /**
     * @return int articles count
     */
    private function articlesCount(): int
    {
        return Article::find()->count();
    }

    /**
     * @param Article $article
     * @param array $wordsCount
     */
    private function countWordsInArticle(Article $article, array &$wordsCount): void
    {
        $text = preg_replace('/[^a-z0-9 ]/', ' ', mb_strtolower($article->text));
        $words = preg_split('/\s/', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            if (!isset($wordsCount[$word])) {
                $wordsCount[$word] = 1;
            } else {
                $wordsCount[$word]++;
            }
        }
    }

    /**
     * @return array
     */
    private function countPerTag(): array
    {
        /** @var Article[] $articles */
        $articles = Article::find()->all();
        $tagsCount = [];

        foreach ($articles as $article) {
            foreach ($article->tags as $tag) {
                if (!isset($tagsCount[$tag->name])) {
                    $tagsCount[$tag->name] = 1;
                } else {
                    $tagsCount[$tag->name]++;
                }
            }
        }

        return $tagsCount;
    }
}
