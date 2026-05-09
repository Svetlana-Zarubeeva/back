<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Svetlana\PhpRestApi\Controllers\LegalEntitiesController;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Middleware для JSON
$app->addBodyParsingMiddleware();

// Health check
$app->get('/health', function ($req, $res) {
    try {
        $db = \Svetlana\PhpRestApi\Models\Database::getConnection();
        $db->query('SELECT 1');
        $status = 'OK';
    } catch (Exception $e) {
        $status = 'ERROR: ' . $e->getMessage();
    }
    return $res->withJson(['status' => $status, 'timestamp' => time()]);
});

// GET /legal-entities — получить все юрлица
$app->get('/legal-entities', [LegalEntitiesController::class, 'getAll']);

// GET /legal-entities/{inn} — получить по ИНН
$app->get('/legal-entities/{inn}', [LegalEntitiesController::class, 'getByInn']);

$app->run();