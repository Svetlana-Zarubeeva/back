<?php

return [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'dbname' => $_ENV['DB_NAME'] ?? 'praktika_db',
    'user' => $_ENV['DB_USER'] ?? 'praktika_user',
    'password' => $_ENV['DB_PASSWORD'] ?? 'secure_password',
];