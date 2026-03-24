<?php
// ============================================================================
// Decoration Core — API Gateway
// ระบบเครื่องราชอิสริยาภรณ์ กระทรวงยุติธรรม
// Single entry point: ทุก request ผ่าน .htaccess → api.php
// ============================================================================
header('Content-Type: application/json; charset=UTF-8');

// CORS — รองรับ dev + production
$allowedOrigins = [
    'http://localhost:5174',
    'http://localhost:8081',
    'https://decoration-core.onrender.com'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: http://localhost:5174');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'config.php';
include 'auth.php';
include_once 'helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = explode('/', trim($uri, '/'));

// ตัด 'api' ออกจาก path ถ้ามี
if ($path[0] === 'api') {
    array_shift($path);
}

$token = getAuthHeader();

// ข้าม auth สำหรับ login + OPTIONS
if (!in_array($path[0], ['login', 'auth']) && $method !== 'OPTIONS') {
    if (!$token || !validateJWT($token)) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

// ============================================================================
// Route Dispatching
// ============================================================================
switch ($path[0]) {

    // ==================== AUTH ====================
    case 'auth':
        if (($path[1] ?? '') === 'login' && $method === 'POST') {
            $data = getJsonInput();
            $email = $data['email'] ?? $data['username'] ?? '';
            $password = $data['password'] ?? '';

            // Demo credentials (ควรเช็คกับ DB จริง)
            if (($email === 'admin@decoration.moj.go.th' || $email === 'admin') && $password === 'admin123') {
                $token = generateJWT(1, 'admin');
                jsonResponse([
                    'token' => $token,
                    'user' => [
                        'id' => 1,
                        'email' => 'admin@decoration.moj.go.th',
                        'name' => 'ผู้ดูแลระบบ',
                        'role' => 'admin'
                    ]
                ]);
            } else {
                jsonResponse(['error' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
            }
        }
        break;

    case 'login':
        if ($method === 'POST') {
            $data = getJsonInput();
            $email = $data['email'] ?? $data['username'] ?? '';
            $password = $data['password'] ?? '';

            if (($email === 'admin@decoration.moj.go.th' || $email === 'admin') && $password === 'admin123') {
                $token = generateJWT(1, 'admin');
                jsonResponse([
                    'token' => $token,
                    'user' => [
                        'id' => 1,
                        'email' => 'admin@decoration.moj.go.th',
                        'name' => 'ผู้ดูแลระบบ',
                        'role' => 'admin'
                    ]
                ]);
            } else {
                jsonResponse(['error' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
            }
        }
        break;

    // ==================== DASHBOARD ====================
    case 'dashboard':
        if ($method === 'GET') {
            $pdo = getDB();
            include __DIR__ . '/routes/dashboard.php';
            handleDashboard($pdo);
        }
        break;

    // ==================== PERSONNEL (ค้นหาบุคลากร) ====================
    case 'personnel':
        if ($method === 'GET') {
            $pdo = getDB();
            $search = $_GET['search'] ?? '';
            $limit = intval($_GET['limit'] ?? 10);

            if (empty($search)) {
                jsonResponse(['success' => true, 'data' => []]);
            }

            $searchTerm = "%{$search}%";
            $stmt = $pdo->prepare("
                SELECT p.personnel_id, p.citizen_id,
                       CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                       p.first_name, p.last_name,
                       p.current_level_code,
                       p.hire_date,
                       pos.position_name AS current_position,
                       o.org_name AS department
                FROM personnel p
                LEFT JOIN `position` pos ON p.current_position_id = pos.position_id
                LEFT JOIN organization o ON p.current_org_id = o.org_id
                WHERE p.is_active = 1
                  AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.citizen_id LIKE ?)
                ORDER BY p.first_name, p.last_name
                LIMIT $limit
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        break;

    // ==================== ช้างเผือก-มงกุฎไทย ====================
    case 'decorations':
        $pdo = getDB();
        include __DIR__ . '/routes/decorations.php';
        handleDecorations($pdo, $method, $path);
        break;

    // ==================== ดิเรกคุณาภรณ์ ====================
    case 'direk':
        $pdo = getDB();
        include __DIR__ . '/routes/direk.php';
        handleDirek($pdo, $method, $path);
        break;

    // ==================== เหรียญจักรพรรดิมาลา ====================
    case 'chakrabardi':
        $pdo = getDB();
        include __DIR__ . '/routes/chakrabardi.php';
        handleChakrabardi($pdo, $method, $path);
        break;

    // ==================== FILES ====================
    case 'files':
        $pdo = getDB();
        include __DIR__ . '/routes/files.php';
        handleFiles($pdo, $method, $path);
        break;

    // ==================== USERS ====================
    case 'users':
        $pdo = getDB();
        include __DIR__ . '/routes/users.php';
        handleUsers($pdo, $method, $path);
        break;

    // ==================== SETTINGS ====================
    case 'settings':
        $pdo = getDB();
        include __DIR__ . '/routes/settings.php';
        handleSettings($pdo, $method, $path);
        break;

    // ==================== ROUNDS (รอบเสนอขอ) ====================
    case 'rounds':
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM request_rounds ORDER BY round_year DESC, created_at DESC");
        $stmt->execute();
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ==================== ROLES ====================
    case 'roles':
        $pdo = getDB();
        $stmt = $pdo->query("SELECT * FROM roles ORDER BY role_id");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    default:
        jsonResponse(['error' => 'Not found'], 404);
}
