<?php
// ============================================================================
// helpers.php — Decoration Core
// ฟังก์ชันช่วยเหลือสำหรับระบบเครื่องราชอิสริยาภรณ์
// ============================================================================

/**
 * แปลงวันที่เป็นรูปแบบไทย (พ.ศ.)
 */
function formatThaiDate(?string $dateStr): ?string {
    if ($dateStr === null || $dateStr === '') return null;

    $thaiMonths = [
        '', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
        'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
    ];

    $timestamp = strtotime($dateStr);
    if ($timestamp === false) return null;

    $day = (int) date('j', $timestamp);
    $month = (int) date('n', $timestamp);
    $year = (int) date('Y', $timestamp) + 543;

    return "{$day} {$thaiMonths[$month]} {$year}";
}

/**
 * แปลงรหัสระดับเป็นชื่อภาษาไทย
 */
function getLevelName(string $code): string {
    $levelNames = [
        'K1' => 'ปฏิบัติการ',
        'K2' => 'ชำนาญการ',
        'K3' => 'ชำนาญการพิเศษ',
        'K4' => 'เชี่ยวชาญ',
        'K5' => 'ทรงคุณวุฒิ',
        'O1' => 'ปฏิบัติงาน',
        'O2' => 'ชำนาญงาน',
        'O3' => 'อาวุโส',
        'D1' => 'อำนวยการ ต้น',
        'D2' => 'อำนวยการ สูง',
        'M1' => 'บริหาร ต้น',
        'M2' => 'บริหาร สูง',
    ];
    return $levelNames[$code] ?? $code;
}

/**
 * คำนวณอายุราชการ (ปี เดือน วัน) จากวันบรรจุ
 */
function calculateServiceYears(string $startDate, ?string $endDate = null): array {
    $start = new DateTime($startDate);
    $end = $endDate ? new DateTime($endDate) : new DateTime();
    $diff = $start->diff($end);

    return [
        'years' => $diff->y,
        'months' => $diff->m,
        'days' => $diff->d,
        'total_years' => round($diff->y + $diff->m / 12 + $diff->d / 365, 1),
        'text' => "{$diff->y} ปี {$diff->m} เดือน {$diff->d} วัน"
    ];
}

/**
 * ส่ง JSON response พร้อม status code
 */
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * อ่าน JSON body จาก request
 */
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

/**
 * Pagination helper — สร้าง SQL LIMIT/OFFSET + metadata
 */
function paginate(PDO $pdo, string $countSql, string $dataSql, array $params = [], int $page = 1, int $perPage = 20): array {
    // นับ total
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // ดึง data
    $offset = ($page - 1) * $perPage;
    $dataSql .= " LIMIT $perPage OFFSET $offset";
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'data' => $rows,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
            'has_more' => ($offset + $perPage) < $total
        ]
    ];
}
