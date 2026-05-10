<?php

namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $cfg = require __DIR__ . '/../config/database.php';

            $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']}";
            $this->pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new PDOException("DB connection failed: " . $e->getMessage());
        }
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
}