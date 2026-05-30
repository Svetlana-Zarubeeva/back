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
            $district = $queryParams['district'] ?? null;
            $search = $queryParams['search'] ?? null;
            $onlyActive = isset($queryParams['only_active']) ? filter_var($queryParams['only_active'], FILTER_VALIDATE_BOOLEAN) : false;
            $lat = isset($queryParams['lat']) ? (float)$queryParams['lat'] : null;
            $lng = isset($queryParams['lng']) ? (float)$queryParams['lng'] : null;
            $limit = (int)($queryParams['limit'] ?? 20);
            $offset = (int)($queryParams['offset'] ?? 0);
            
            $db = \App\Models\Database::getConnection();
            
            $sql = "
                SELECT 
                    le.id, le.inn, le.ogrn, le.kpp, le.short_name, le.full_name,
                    le.registration_date, le.status, le.okved_code,
                    a.address_full, a.postal_code, a.region, a.city, a.district, a.street,
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
            
            // Поиск по названию
            if ($search) {
                $searchTerm = trim(strtolower($search));
                $conditions[] = "(le.short_name ILIKE :search OR le.full_name ILIKE :search)";
                $params[':search'] = '%' . $searchTerm . '%';
            }
            
            // ИСПРАВЛЕНО: Фильтр по статусу (регистронезависимый)
            if ($status) {
                // Используем LOWER() для обеих сторон, чтобы 'Не действует' совпало с 'не действует'
                $conditions[] = "LOWER(le.status) = LOWER(:status)";
                $params[':status'] = $status;
            }
            
            // Фильтр по городу (регистронезависимый)
            if ($city) {
                $conditions[] = "LOWER(a.city) = LOWER(:city)";
                $params[':city'] = $city;
            }
            
            // Фильтр по району (регистронезависимый)
            if ($district) {
                $conditions[] = "LOWER(a.district) = LOWER(:district)";
                $params[':district'] = $district;
            }
            
            if ($onlyActive) {
                $conditions[] = "(le.status = 'Действует' OR le.status = 'ACTIVE')";
            }
            
            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }
            
            // Сортировка
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
            
            $result = [];
            foreach ($companies as $company) {
                if ($company['id']) {
                    $foundersStmt = $db->prepare("
                        SELECT f.*, ft.type_name as founder_type_name
                        FROM founders f
                        LEFT JOIN founder_types ft ON f.founder_type_id = ft.id
                        WHERE f.legal_entity_id = :entity_id
                    ");
                    $foundersStmt->bindValue(':entity_id', $company['id']);
                    $foundersStmt->execute();
                    $founders = $foundersStmt->fetchAll(\PDO::FETCH_ASSOC);
                    
                    $phones = $this->parsePhoneData($company['phones'] ?? '');
                    $emails = $this->parseEmailData($company['emails'] ?? '');
                    $websites = $this->parseWebsiteData($company['websites'] ?? '');
                    
                    $companyData = [
                        'id' => $company['id'],
                        'inn' => $company['inn'],
                        'ogrn' => $company['ogrn'],
                        'kpp' => $company['kpp'],
                        'short_name' => $company['short_name'],
                        'full_name' => $company['full_name'],
                        'registration_date' => $company['registration_date'],
                        'status' => $company['status'],
                        'okved_code' => $company['okved_code'],
                        'address' => [
                            'address_full' => $company['address_full'],
                            'postal_code' => $company['postal_code'],
                            'region' => $company['region'],
                            'city' => $company['city'],
                            'district' => $company['district'],
                            'street' => $company['street'],
                            'house' => $company['house'],
                            'flat' => $company['flat'],
                            'latitude' => $company['latitude'],
                            'longitude' => $company['longitude']
                        ],
                        'contact_info' => [
                            'phones' => $phones,
                            'emails' => $emails,
                            'websites' => $websites
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
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            
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
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    // ... (Остальные методы getByInn и парсеры остаются без изменений) ...
    
    public function getByInn(Request $request, Response $response, array $args): Response
    {
        $inn = $args['inn'];
        
        try {
            $db = \App\Models\Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT 
                    le.id, le.inn, le.ogrn, le.kpp, le.short_name, le.full_name,
                    le.registration_date, le.status, le.okved_code,
                    a.address_full, a.postal_code, a.region, a.city, a.district, a.street,
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
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
            
            $foundersStmt = $db->prepare("
                SELECT f.*, ft.type_name as founder_type_name
                FROM founders f
                LEFT JOIN founder_types ft ON f.founder_type_id = ft.id
                WHERE f.legal_entity_id = :entity_id
            ");
            $foundersStmt->bindValue(':entity_id', $company['id']);
            $foundersStmt->execute();
            $founders = $foundersStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $phones = $this->parsePhoneData($company['phones'] ?? '');
            $emails = $this->parseEmailData($company['emails'] ?? '');
            $websites = $this->parseWebsiteData($company['websites'] ?? '');
            
            $result = [
                'id' => $company['id'],
                'inn' => $company['inn'],
                'ogrn' => $company['ogrn'],
                'kpp' => $company['kpp'],
                'short_name' => $company['short_name'],
                'full_name' => $company['full_name'],
                'registration_date' => $company['registration_date'],
                'status' => $company['status'],
                'okved_code' => $company['okved_code'],
                'address' => [
                    'address_full' => $company['address_full'],
                    'postal_code' => $company['postal_code'],
                    'region' => $company['region'],
                    'city' => $company['city'],
                    'district' => $company['district'],
                    'street' => $company['street'],
                    'house' => $company['house'],
                    'flat' => $company['flat'],
                    'latitude' => $company['latitude'],
                    'longitude' => $company['longitude']
                ],
                'contact_info' => [
                    'phones' => $phones,
                    'emails' => $emails,
                    'websites' => $websites
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
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
    
    private function parsePhoneData($data) {
        if (empty($data) || $data === null || $data === '') return [];
        if (is_array($data)) {
            return array_map(function($phone) {                
                if (is_string($phone)) return ['number' => $phone];
                elseif (isset($phone['number'])) return ['number' => $phone['number']];
                elseif (isset($phone['phone'])) return ['number' => $phone['phone']];
                else return ['number' => (string)$phone];
            }, $data);
        }
        if (is_string($data)) {
            $data = trim($data);
            if ($data === '[]') return [];
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return array_map(function($phone) {
                    if (is_string($phone)) return ['number' => $phone];
                    elseif (isset($phone['number'])) return ['number' => $phone['number']];
                    elseif (isset($phone['phone'])) return ['number' => $phone['phone']];
                    else return ['number' => (string)$phone];
                }, $decoded);
            }
            return [];
        }
        return [];
    }
    
    private function parseEmailData($data) {
        if (empty($data) || $data === null || $data === '') return [];
        if (is_array($data)) {
            return array_map(function($email) {                
                if (is_string($email)) return ['email' => $email];
                elseif (isset($email['email'])) return ['email' => $email['email']];
                else return ['email' => (string)$email];
            }, $data);
        }
        if (is_string($data)) {
            $data = trim($data);
            if ($data === '[]') return [];
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return array_map(function($email) {
                    if (is_string($email)) return ['email' => $email];
                    elseif (isset($email['email'])) return ['email' => $email['email']];
                    else return ['email' => (string)$email];
                }, $decoded);
            }
            return [];
        }
        return [];
    }
    
    private function parseWebsiteData($data) {
        if (empty($data) || $data === null || $data === '') return [];
        if (is_array($data)) {
            return array_map(function($website) {                
                if (is_string($website)) return ['url' => $website];
                elseif (isset($website['url'])) return ['url' => $website['url']];
                else return ['url' => (string)$website];
            }, $data);
        }
        if (is_string($data)) {
            $data = trim($data);
            if ($data === '[]') return [];
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return array_map(function($website) {
                    if (is_string($website)) return ['url' => $website];
                    elseif (isset($website['url'])) return ['url' => $website['url']];
                    else return ['url' => (string)$website];
                }, $decoded);
            }
            return [];
        }
        return [];
    }
}