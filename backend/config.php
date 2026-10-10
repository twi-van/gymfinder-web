<?php
/**
 * GYMFINDER – cấu hình (Sprint 0 / TV2 Backend + Database)
 * Schema/seed MySQL: database/schema.sql + database/seed.sql
 * Demo mặc định: SQLite tự tạo lần chạy đầu (database/gymfinder.sqlite)
 */
return [
    'driver' => getenv('GF_DB_DRIVER') ?: 'sqlite',
    'sqlite_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'gymfinder.sqlite',
    'mysql' => [
        'host' => getenv('GF_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('GF_DB_PORT') ?: '3306',
        'database' => getenv('GF_DB_NAME') ?: 'gymfinder',
        'username' => getenv('GF_DB_USER') ?: 'root',
        'password' => getenv('GF_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
];
