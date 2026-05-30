<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DistrictController
{
    /**
     * Получить список уникальных районов
     */
    public function getDistricts(Request $request, Response $response): Response
    {
        try {
            $db = \App\Models\Database::getConnection();
            
            // Получаем все значения
            $stmt = $db->query("
                SELECT district 
                FROM addresses 
                WHERE district IS NOT NULL AND district != ''
            ");
            
            $rawDistricts = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // --- ОТЛАДКА: Выводим количество сырых данных ---
            error_log("[DistrictController] Найдено сырых записей: " . count($rawDistricts));
            if (!empty($rawDistricts)) {
                // Выводим первые 3 записи для примера
                $samples = array_slice($rawDistricts, 0, 3);
                error_log("[DistrictController] Примеры сырых данных: " . json_encode($samples));
            }
            // -----------------------------------------------
            
            // Нормализуем и убираем дубликаты на стороне PHP
            $uniqueDistricts = [];
            
            foreach ($rawDistricts as $district) {
                if (empty($district)) continue;
                
                // Очищаем: удаляем лишние пробелы, приводим к нижнему регистру
                $normalized = trim(preg_replace('/\s+/', ' ', $district));
                $lowerNormalized = mb_strtolower($normalized, 'UTF-8');
                
                // Используем нормализованную версию как ключ для уникальности
                if (!isset($uniqueDistricts[$lowerNormalized])) {
                    // Сохраняем красивый вариант написания (первый встретившийся)
                    $uniqueDistricts[$lowerNormalized] = $normalized;
                }
            }
            
            // --- ОТЛАДКА: Выводим количество уникальных районов ---
            error_log("[DistrictController] Уникальных районов после нормализации: " . count($uniqueDistricts));
            
            // Выводим первые 3 уникальных района для проверки
            $uniqueSamples = array_slice($uniqueDistricts, 0, 3, true); // true сохраняет ключи
            foreach ($uniqueSamples as $key => $val) {
                error_log("[DistrictController] Уникальный район [KEY: '$key'] => [VAL: '$val']");
            }
            // ----------------------------------------------------
            
            // Сортируем по алфавиту
            ksort($uniqueDistricts);
            
            $districts = [];
            foreach ($uniqueDistricts as $normKey => $originalText) {
                $districts[] = [
                    'id' => $normKey,      // Для value в option используем очищенный lowercase
                    'name' => $originalText // Для отображения используем красивый текст
                ];
            }
            
            $responseData = json_encode([
                'success' => true,
                'data' => $districts
            ]);

            // --- ОТЛАДКА: Выводим итоговый JSON (первые 200 символов) ---
            error_log("[DistrictController] Отправка ответа (начало): " . substr($responseData, 0, 200) . "...");
            // ----------------------------------------------------------
            
            $response->getBody()->write($responseData);
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            
        } catch (\Exception $e) {
            $debugMode = $_SERVER['APP_DEBUG'] ?? false;
            $errorMessage = $debugMode ? $e->getMessage() : 'Internal server error';
            
            // --- ОТЛАДКА ОШИБКИ ---
            error_log("[DistrictController] ОШИБКА: " . $e->getMessage());
            error_log("[DistrictController] Трассировка: " . $e->getTraceAsString());
            // ----------------------
            
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