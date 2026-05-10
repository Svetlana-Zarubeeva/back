<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use App\Controllers\CompanyController;
use App\Controllers\CityController;
use App\Controllers\StatusController;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

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