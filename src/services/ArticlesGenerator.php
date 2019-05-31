<?php

namespace app\services;

use app\models\Article;
use app\models\ArticleTags;
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
    private const POSSIBLE_TAGS = [
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

    /**
     * @var LoremIpsum
     */
    private $ipsum;

    /**
     * @var Tag[]
     * @psalm-var array<string, Tag>
     */
    private $ensuredTags = [];

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
            $articles[] = $this->createRandomArticle();
        }

        return $articles;
    }

    /**
     * @param int $number Number of articles to be generated
     * @return Article[]
     */
    public function generateBatch(int $number): array
    {
        $this->fillupTags();
        return $this->generate($number);
    }

    private function createRandomArticle(): Article
    {
        $article = new Article([
            'title' => $this->generateRandomTitle(),
            'text' => $this->generateRandomText(),
        ]);
        $article->save();

        $rows = [];
        $tags = $this->generateTags();
        foreach ($tags as $tag) {
            $rows[] = [$article->id, $tag->id];
        }

        \Yii::$app->db
            ->createCommand()
            ->batchInsert(ArticleTags::tableName(), ['article_id', 'tag_id'], $rows)
            ->execute();

        $article->populateRelation('tags', $tags);

        return $article;
    }

    private function getRandomTag(): Tag
    {
        return $this->ensureTag(
            $this->lookupRandomTagName()
        );
    }

    private function lookupRandomTagName(): string
    {
        $i = random_int(0, count(self::POSSIBLE_TAGS) - 1);

        return self::POSSIBLE_TAGS[$i];
    }

    private function ensureTag(string $name): Tag
    {
        if (isset($this->ensuredTags[$name])) {
            return $this->ensuredTags[$name];
        }

        if ($tag = Tag::find()->where(['name' => $name])->one()) {
            $this->ensuredTags[$name] = $tag;
            return $tag;
        }

        $tag = new Tag(['name' => $name]);
        $tag->save();
        $this->ensuredTags[$name] = $tag;

        return $tag;
    }

    /**
     * @return Tag[]
     */
    private function generateTags(): array
    {
        $count = random_int(1, 5);

        $tags = [];
        for ($i = 0; $i < $count; $i++) {
            $tags[] = $this->getRandomTag();
        }

        return $tags;
    }

    private function generateRandomTitle(): string
    {
        return $this->ipsum->words(8);
    }

    private function generateRandomText(): string
    {
        return $this->ipsum->paragraphs(2);
    }

    private function fillupTags(): void
    {
        $newTagNames = [];
        $newRows = [];
        $existedTags = $this->fetchTagsByNames(self::POSSIBLE_TAGS);
        foreach (self::POSSIBLE_TAGS as $possibleTag) {
            if (isset($existedTags[$possibleTag])) {
                continue;
            }

            $newTagNames[] = $possibleTag;
            $newRows[] = [$possibleTag];
        }

        if ([] === $newRows) {
            return;
        }

        \Yii::$app->db
            ->createCommand()
            ->batchInsert(Tag::tableName(), ['name'], $newRows)
            ->execute();

        $this->fetchTagsByNames($newTagNames);
    }

    private function fetchTagsByNames(array $names): array
    {
        $existedTags = Tag::find()
            ->where(['name' => $names])
            ->indexBy('name')
            ->all();

        foreach ($existedTags as $tagName => $existedTag) {
            $this->ensuredTags[$tagName] = $existedTag;
        }

        return $existedTags;
    }
}
