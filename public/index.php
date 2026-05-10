<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use App\Controllers\CompanyController;
use App\Controllers\CityController;
use App\Controllers\StatusController;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

// CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Эндпоинты компаний
$app->get('/companies', [CompanyController::class, 'getAll']);
$app->get('/companies/{inn}', [CompanyController::class, 'getByInn']);

// Эндпоинты для фильтрации
$app->get('/cities', [CityController::class, 'getCities']);
$app->get('/statuses', [StatusController::class, 'getStatuses']);

// Обработка ошибок 404
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    return $response->withStatus(404)->withJson(['success' => false, 'error' => 'Route not found']);
});

$app->run();