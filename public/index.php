<?php
declare(strict_types=1);

// Сначала подключаем автозагрузчик
require __DIR__ . '/../vendor/autoload.php';

// Теперь можем использовать классы
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use App\Controllers\CompanyController;
use App\Controllers\CityController;
use App\Controllers\DistrictController;
use App\Controllers\StatusController;

// Загружаем переменные окружения
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Эндпоинты компаний
$app->get('/companies', [CompanyController::class, 'getAll']);
$app->get('/companies/{inn}', [CompanyController::class, 'getByInn']);

// Эндпоинты для фильтрации
$app->get('/cities', [CityController::class, 'getCities']);
$app->get('/districts', [DistrictController::class, 'getDistricts']); 
$app->get('/statuses', [StatusController::class, 'getStatuses']);

// Обработка ошибок 404
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    return $response->withStatus(404)->withJson(['success' => false, 'error' => 'Route not found']);
});

// CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->run();