<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use App\Middleware\JwtMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(APP_ROOT);
$dotenv->load();

$builder = new ContainerBuilder;

$container = $builder->addDefinitions(APP_ROOT.'/config/definitions.php')
                     ->build();

AppFactory::setContainer($container);

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$app->add(function (Request $request, $handler) {
    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$secretKey = $_ENV['JWT_SECRET_KEY'];
$jwtMiddleware = new JwtMiddleware($secretKey);

(require APP_ROOT . '/src/App//Routes/AuthRoutes.php')($app);
(require APP_ROOT . '/src/App/Routes/GasStationsRoutes.php')($app);
(require APP_ROOT . '/src/App/Routes/PriceDataRoutes.php')($app);
(require APP_ROOT . '/src/App/Routes/OrdersRoutes.php')($app);

$app->run();