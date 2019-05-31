<?php

namespace app\console;

use app\services\StatsGenerator;
use Blackfire\Client;
use Blackfire\LoopClient;
use Blackfire\Profile\Configuration;
use Yii;
use yii\base\Module;
use yii\console\Controller;
use yii\console\widgets\Table;
use yii\helpers\Console;

/**
 * Class DaemonController
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class DaemonController extends Controller
{
    /**
     * @var StatsGenerator
     */
    private $stats;

    public function __construct($id, Module $module, StatsGenerator $stats, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->stats = $stats;
    }

    public function actionIndex($limit = 3)
    {
        $taskFile = Yii::getAlias('@runtime/task.txt');
        file_put_contents($taskFile, '');

        echo ' ✔️ Daemon is ready.', PHP_EOL;
        echo '    Run `echo 1 > runtime/task.txt` to run worker', PHP_EOL;

        while ($limit > 0) {
            $result = file_get_contents($taskFile);
            if (empty($result)) {
                continue;
            }

            $limit--;
            file_put_contents($taskFile, '');
            echo ' [NEW] Consumed task at ', date(DATE_ATOM), PHP_EOL;

            $this->loopHandler();

            echo PHP_EOL, ' [OK] Task done', PHP_EOL, PHP_EOL;
        }

        echo ' [x_x] Consumed max messages per daemon. Shutting down.';
    }

    private function handler(): void
    {
        $blackfire = new Client();
        $config = new Configuration();
        $config->setTitle('Daemon run at ' . (new \DateTime())->format(DATE_ATOM));

        $probe = $blackfire->createProbe($config);

        $stats = $this->stats->generate();

        echo Console::renderColoredString('%NTotal articles:%n ' . $stats->getArticlesCount() . PHP_EOL);

        $table = new Table();
        $table->setHeaders(['Word', 'Count']);
        $rows = [];
        foreach ($stats->getTopWords(10) as $word => $count) {
            $rows[] = [$word, $count];
        }
        $table->setRows($rows);
        echo $table->run();


        $table = new Table();
        $table->setHeaders(['Tag', 'Count']);
        $rows = [];
        foreach ($stats->getTopTags(5) as $tag => $count) {
            $rows[] = [$tag, $count];
        }
        $table->setRows($rows);

        echo $table->run();
        echo PHP_EOL;

        $profile = $blackfire->endProbe($probe);
        echo 'PROFILE >> ', $profile->getUrl();
    }

    private function loopHandler(): void
    {
        $blackfire = new LoopClient(new Client(), 3);
        $config = new Configuration();
        $config->setTitle('Daemon run at ' . (new \DateTime())->format(DATE_ATOM));

        for ($i = 0; $i < 5; $i++) {
            $blackfire->startLoop($config);
            $stats = $this->stats->generate();
            $profile = $blackfire->endLoop();

            if ($profile !== null) {
                echo ' >> PROFILE: ', $profile->getUrl();
            }
        }
    }
}
