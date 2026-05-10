<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StatusController
{
    /**
     * Получить список уникальных статусов
     */
    public function getStatuses(Request $request, Response $response): Response
    {
        try {
            $db = \App\Models\Database::getConnection();
            
            $stmt = $db->query("
                SELECT DISTINCT status 
                FROM legal_entities 
                WHERE status IS NOT NULL AND status != ''
                ORDER BY status
            ");
            $statuses = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $statuses
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            
        } catch (\Exception $e) {
            $debugMode = $_SERVER['APP_DEBUG'] ?? false;
            $errorMessage = $debugMode ? $e->getMessage() : 'Internal server error';
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Database error',
                'message' => $errorMessage
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}