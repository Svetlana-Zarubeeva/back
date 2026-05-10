<?php

namespace App\Controllers;

use App\Models\LegalEntity;
use App\Models\Address;
use App\Models\ContactInfo;
use App\Models\Finance;
use App\Models\Management;
use App\Models\Founder;
use App\Models\FounderType;
use App\Helpers\DistanceHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CompanyController
{
    /**
     * Получить список компаний с фильтрацией и пагинацией
     */
    public function getAll(Request $request, Response $response): Response
    {
        try {
            // Получаем параметры запроса
            $queryParams = $request->getQueryParams();
            $status = $queryParams['status'] ?? null;
            $city = $queryParams['city'] ?? null;
            $onlyActive = isset($queryParams['only_active']) ? filter_var($queryParams['only_active'], FILTER_VALIDATE_BOOLEAN) : false;
            $lat = isset($queryParams['lat']) ? (float)$queryParams['lat'] : null;
            $lng = isset($queryParams['lng']) ? (float)$queryParams['lng'] : null;
            $limit = (int)($queryParams['limit'] ?? 20);
            $offset = (int)($queryParams['offset'] ?? 0);
            
            $db = \App\Models\Database::getConnection();
            
            $sql = "
                SELECT 
                    le.id, le.inn, le.ogrn, le.kpp, le.short_name, le.full_name,
                    le.registration_date, le.status, le.okved_id,
                    a.address_full, a.postal_code, a.region, a.city, a.street,
                    a.house, a.flat, a.latitude, a.longitude,
                    ci.phones, ci.emails, ci.websites,
                    f.employee_count, f.revenue, f.income, f.expense, f.tax_system,
                    m.name as management_name, m.post as management_post, m.start_date as management_start_date
                FROM legal_entities le
                LEFT JOIN addresses a ON le.id = a.legal_entity_id
                LEFT JOIN contact_info ci ON le.id = ci.legal_entity_id  
                LEFT JOIN finance f ON le.id = f.legal_entity_id
                LEFT JOIN management m ON le.id = m.legal_entity_id
                WHERE 1=1
            ";
            
            $conditions = [];
            $params = [];
            
            if ($status) {
                $conditions[] = "le.status = :status";
                $params[':status'] = $status;
            }
            
            if ($city) {
                $conditions[] = "a.city = :city";
                $params[':city'] = $city;
            }
            
            if ($onlyActive) {
                $conditions[] = "le.status = 'ACTIVE'";
            }
            
            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }
            
            // Сортировка по расстоянию или по умолчанию
            if ($lat !== null && $lng !== null) {
                $sql .= " ORDER BY 
                    (a.latitude - :lat) * (a.latitude - :lat) + 
                    (a.longitude - :lng) * (a.longitude - :lng)";
                $params[':lat'] = $lat;
                $params[':lng'] = $lng;
            } else {
                $sql .= " ORDER BY le.created_at DESC";
            }
            
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, \PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            $companies = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Получаем учредителей для каждой компании
            $result = [];
            foreach ($companies as $company) {
                if ($company['id']) {
                    // Получаем учредителей
                    $foundersStmt = $db->prepare("
                        SELECT f.*, ft.type_name as founder_type_name
                        FROM founders f
                        LEFT JOIN founder_types ft ON f.founder_type_id = ft.id
                        WHERE f.legal_entity_id = :entity_id
                    ");
                    $foundersStmt->bindValue(':entity_id', $company['id']);
                    $foundersStmt->execute();
                    $founders = $foundersStmt->fetchAll(\PDO::FETCH_ASSOC);
                    
                    $companyData = [
                        'id' => $company['id'],
                        'inn' => $company['inn'],
                        'ogrn' => $company['ogrn'],
                        'kpp' => $company['kpp'],
                        'short_name' => $company['short_name'],
                        'full_name' => $company['full_name'],
                        'registration_date' => $company['registration_date'],
                        'status' => $company['status'],
                        'okved_id' => $company['okved_id'],
                        'address' => [
                            'address_full' => $company['address_full'],
                            'postal_code' => $company['postal_code'],
                            'region' => $company['region'],
                            'city' => $company['city'],
                            'street' => $company['street'],
                            'house' => $company['house'],
                            'flat' => $company['flat'],
                            'latitude' => $company['latitude'],
                            'longitude' => $company['longitude']
                        ],
                        'contact_info' => [
                            'phones' => $company['phones'],
                            'emails' => $company['emails'],
                            'websites' => $company['websites']
                        ],
                        'finance' => [
                            'employee_count' => $company['employee_count'],
                            'revenue' => $company['revenue'],
                            'income' => $company['income'],
                            'expense' => $company['expense'],
                            'tax_system' => $company['tax_system']
                        ],
                        'management' => [
                            'name' => $company['management_name'],
                            'post' => $company['management_post'],
                            'start_date' => $company['management_start_date']
                        ],
                        'founders' => $founders
                    ];
                    
                    // Добавляем расстояние, если заданы координаты
                    if ($lat !== null && $lng !== null && $company['latitude'] !== null && $company['longitude'] !== null) {
                        $distance = DistanceHelper::calculateDistance(
                            $lat, $lng, 
                            $company['latitude'], $company['longitude']
                        );
                        $companyData['distance_km'] = round($distance, 2);
                    }
                    
                    $result[] = $companyData;
                }
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result,
                'total' => count($result),
                'limit' => $limit,
                'offset' => $offset
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

    /**
     * Получить компанию по ИНН
     */
    public function getByInn(Request $request, Response $response, array $args): Response
    {
        $inn = $args['inn'];
        
        try {
            $db = \App\Models\Database::getConnection();
            
            // Получаем основную информацию
            $stmt = $db->prepare("
                SELECT 
                    le.id, le.inn, le.ogrn, le.kpp, le.short_name, le.full_name,
                    le.registration_date, le.status, le.okved_id,
                    a.address_full, a.postal_code, a.region, a.city, a.street,
                    a.house, a.flat, a.latitude, a.longitude,
                    ci.phones, ci.emails, ci.websites,
                    f.employee_count, f.revenue, f.income, f.expense, f.tax_system,
                    m.name as management_name, m.post as management_post, m.start_date as management_start_date
                FROM legal_entities le
                LEFT JOIN addresses a ON le.id = a.legal_entity_id
                LEFT JOIN contact_info ci ON le.id = ci.legal_entity_id  
                LEFT JOIN finance f ON le.id = f.legal_entity_id
                LEFT JOIN management m ON le.id = m.legal_entity_id
                WHERE le.inn = :inn
                LIMIT 1
            ");
            $stmt->bindValue(':inn', $inn);
            $stmt->execute();
            $company = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$company) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Company not found'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
            
            // Получаем учредителей
            $foundersStmt = $db->prepare("
                SELECT f.*, ft.type_name as founder_type_name
                FROM founders f
                LEFT JOIN founder_types ft ON f.founder_type_id = ft.id
                WHERE f.legal_entity_id = :entity_id
            ");
            $foundersStmt->bindValue(':entity_id', $company['id']);
            $foundersStmt->execute();
            $founders = $foundersStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $result = [
                'id' => $company['id'],
                'inn' => $company['inn'],
                'ogrn' => $company['ogrn'],
                'kpp' => $company['kpp'],
                'short_name' => $company['short_name'],
                'full_name' => $company['full_name'],
                'registration_date' => $company['registration_date'],
                'status' => $company['status'],
                'okved_id' => $company['okved_id'],
                'address' => [
                    'address_full' => $company['address_full'],
                    'postal_code' => $company['postal_code'],
                    'region' => $company['region'],
                    'city' => $company['city'],
                    'street' => $company['street'],
                    'house' => $company['house'],
                    'flat' => $company['flat'],
                    'latitude' => $company['latitude'],
                    'longitude' => $company['longitude']
                ],
                'contact_info' => [
                    'phones' => $company['phones'],
                    'emails' => $company['emails'],
                    'websites' => $company['websites']
                ],
                'finance' => [
                    'employee_count' => $company['employee_count'],
                    'revenue' => $company['revenue'],
                    'income' => $company['income'],
                    'expense' => $company['expense'],
                    'tax_system' => $company['tax_system']
                ],
                'management' => [
                    'name' => $company['management_name'],
                    'post' => $company['management_post'],
                    'start_date' => $company['management_start_date']
                ],
                'founders' => $founders
            ];
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
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