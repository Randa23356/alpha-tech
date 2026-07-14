<?php
// src/config/db.php

require_once __DIR__ . '/config.php';

date_default_timezone_set('Asia/Jakarta');

$charset = 'utf8mb4';
$pdo = null;

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

} catch (\PDOException $e) {
    error_log('DB Connection failed: ' . $e->getMessage());

    if (isset($is_local) && $is_local) {
        die('<h2>Database Connection Failed</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed', 'data' => null]);
        exit();
    }
}
?>