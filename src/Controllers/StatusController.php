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
            
            // 1. Получаем все статусы
            $stmt = $db->query("
                SELECT status 
                FROM legal_entities 
                WHERE status IS NOT NULL AND status != ''
            ");
            
            $rawStatuses = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // 2. Нормализуем и убираем дубликаты на стороне PHP
            $uniqueStatuses = [];
            
            foreach ($rawStatuses as $status) {
                if (empty($status)) continue;
                
                // Очищаем: удаляем лишние пробелы, переносы строк, приводим к нижнему регистру
                // Это гарантирует, что "Действует ", "действует" и "ДЕЙСТВУЕТ" станут одним значением
                $normalized = trim(preg_replace('/\s+/', ' ', $status));
                $lowerNormalized = mb_strtolower($normalized, 'UTF-8');
                
                // Используем оригинальный текст (с правильным регистром первого слова) для красоты,
                // но ключом массива делаем нормализованную версию, чтобы убрать дубли
                if (!isset($uniqueStatuses[$lowerNormalized])) {
                    // Сохраняем первый встретившийся вариант написания (или можно привести к ucwords)
                    $uniqueStatuses[$lowerNormalized] = $normalized;
                }
            }
            
            // 3. Преобразуем в формат для фронтенда
            // Сортируем по алфавиту для удобства
            ksort($uniqueStatuses);
            
            $statuses = [];
            foreach ($uniqueStatuses as $normKey => $originalText) {
                $statuses[] = [
                    'id' => $normKey,      // Для value в option используем очищенный lowercase
                    'name' => $originalText // Для отображения используем красивый текст
                ];
            }
            
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