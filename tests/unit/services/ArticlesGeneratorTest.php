<?php

namespace app\tests;

use app\models\Article;
use app\services\ArticlesGenerator;
use Blackfire\Bridge\PhpUnit\TestCaseTrait;
use Blackfire\Profile\Configuration;
use Blackfire\Profile\Metric;
use joshtronic\LoremIpsum;
use PHPUnit\Framework\TestCase;

/**
 * Class ArticlesGeneratorTest
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class ArticlesGeneratorTest extends TestCase
{
    use TestCaseTrait;

    /**
     * @var ArticlesGenerator
     */
    private $generator;

    public function setUp()
    {
        $this->generator = new ArticlesGenerator(new LoremIpsum());
    }

    public function testGeneratesArticlesWithBlackfire(): void
    {
        $config = new Configuration();
        // define some assertions
        $config
            ->defineMetric(new Metric('tags.search', '=app\models\Tag::find'))
            ->assert('metrics.sql.queries.count < 20', 'SQL queries count')
            ->assert('metrics.tags.search.count < 10', 'Tags search count')// ...
        ;

        $profile = $this->assertBlackfire($config, function () {
            $this->generateOneArticle();
        });
    }

    private function generateOneArticle(): void
    {
        $articles = $this->generator->generate(1);
        $this->assertContainsOnlyInstancesOf(Article::class, $articles);
        $article = $articles[0];
        $this->assertNotEmpty($article->title);
        $this->assertNotEmpty($article->text);
        $this->assertNotEmpty($article->id);
        $this->assertNotEmpty($article->tags);
    }
}
