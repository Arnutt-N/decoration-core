<?php
// ============================================================================
// Database Configuration — Decoration Core
// เชื่อมต่อ TiDB Cloud (decoration_core) ผ่าน SSL port 4000
// ============================================================================

function env($key, $default = '') {
    return getenv($key) ?: ($_ENV[$key] ?? ($_SERVER[$key] ?? $default));
}

header('Content-Type: application/json; charset=UTF-8');

// JWT Secret
define('JWT_SECRET', env('JWT_SECRET', 'decoration_core_secret_2569'));
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// ============================================================================
// Lazy PDO Connection
// สร้าง connection เฉพาะเมื่อ route ต้องใช้ DB จริงๆ
// ============================================================================
$pdo = null;

function getDB(): PDO {
    global $pdo;
    if ($pdo !== null) {
        return $pdo;
    }

    $host     = env('MYSQL_HOST', 'localhost');
    $port     = env('MYSQL_PORT', '4000');
    $dbname   = env('MYSQL_DATABASE', 'decoration_core');
    $username = env('MYSQL_USER', 'root');
    $password = env('MYSQL_PASSWORD', '');
    $useSSL   = env('MYSQL_SSL', 'false');

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // TiDB Cloud ต้องใช้ SSL
    if ($useSSL === 'true' || $useSSL === '1') {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        $options[PDO::MYSQL_ATTR_SSL_CA] = '';
    }

    // Docker local ใช้ persistent connection
    if ($host === 'db' || $host === 'localhost' || $host === '127.0.0.1') {
        $options[PDO::ATTR_PERSISTENT] = true;
    }

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        $msg = ($host === 'db' || $host === 'localhost')
            ? 'Connection failed: ' . $e->getMessage()
            : 'Database connection failed';
        http_response_code(503);
        echo json_encode(['error' => $msg]);
        exit;
    }

    return $pdo;
}
