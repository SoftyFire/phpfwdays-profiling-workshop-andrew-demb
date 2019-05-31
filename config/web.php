<?php

if (file_exists(__DIR__ . '/../.env')) {
    (new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/../.env');
}

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__) . '/src',
    'vendorPath' => dirname(__DIR__) . '/vendor',
    'bootstrap' => ['log', \app\bootstrap\Bootstrap::class],
    'aliases' => [
        '@runtime' => dirname(__DIR__) . '/runtime',
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'assetManager' => [
            'bundles' => [
                \yii\web\JqueryAsset::class => false
            ]
        ],
        'request' => [
            'cookieValidationKey' => 'yes-it-is-absolutely-random',
        ],
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            '__class' => \yii\log\Logger::class,
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
    'container' => [
        'definitions' => [
            \yii\web\JqueryAsset::class => function () {
                return new \yii\web\AssetBundle();
            },
            \app\services\RemoteArticlesProviderInterface::class => function () {
                $params = Yii::$app->params;

                return new \app\services\NewsApiClient(
                    new \GuzzleHttp\Client([
                        'base_uri' => 'https://newsapi.org/v2/',
                        'headers' => [
                            'Accept' => 'application/json',
                        ],
                        'defaults' => [
                            'timeout'         => 2,
                            'allow_redirects' => false,
                        ],
                    ]),
                    $params['newsApiToken'] ?? null
                );
            },
        ]
    ]
];

if (false) { // Enable when speaker asks to
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];
}

return $config;
