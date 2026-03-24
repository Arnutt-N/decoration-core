<?php
// ============================================================================
// Dashboard Route — สรุปภาพรวมระบบเครื่องราชอิสริยาภรณ์
// ============================================================================

function handleDashboard(PDO $pdo): void {
    // จำนวนบุคลากร
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM personnel WHERE is_active = 1");
    $totalPersonnel = (int) $stmt->fetch()['total'];

    // คำขอช้างเผือก-มงกุฎไทย
    $changpuakStats = getRequestStats($pdo, 'decoration_changpuak_requests');

    // คำขอดิเรกคุณาภรณ์
    $direkStats = getRequestStats($pdo, 'direk_requests');

    // คำขอเหรียญจักรพรรดิมาลา
    $chakrabardiStats = getRequestStats($pdo, 'chakrabardi_requests');

    // จำนวนอาสาสมัคร (ดิเรกฯ)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM direk_persons WHERE is_active = 1");
    $totalVolunteers = (int) $stmt->fetch()['total'];

    jsonResponse([
        'success' => true,
        'total_personnel' => $totalPersonnel,
        'total_volunteers' => $totalVolunteers,
        'changpuak' => $changpuakStats,
        'direk' => $direkStats,
        'chakrabardi' => $chakrabardiStats,
        'summary' => [
            'pending_requests' => $changpuakStats['pending'] + $direkStats['pending'] + $chakrabardiStats['pending'],
            'granted_this_year' => $changpuakStats['granted'] + $direkStats['granted'] + $chakrabardiStats['granted'],
        ]
    ]);
}

/**
 * นับจำนวนคำขอแยกตามสถานะ จากตาราง request ใดๆ
 */
function getRequestStats(PDO $pdo, string $table): array {
    $currentYear = (int) date('Y') + 543; // พ.ศ.

    try {
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as cnt
            FROM $table
            WHERE request_year = ?
            GROUP BY status
        ");
        $stmt->execute([$currentYear]);
        $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
        // ตารางอาจยังไม่มี → return ค่าว่าง
        return ['total' => 0, 'pending' => 0, 'granted' => 0, 'by_status' => []];
    }

    $byStatus = [];
    $total = 0;
    $pending = 0;
    $granted = 0;

    foreach ($rows as $row) {
        $s = $row['status'];
        $c = (int) $row['cnt'];
        $byStatus[$s] = $c;
        $total += $c;

        if (in_array($s, ['draft', 'submitted', 'screening', 'committee_review'])) {
            $pending += $c;
        }
        if ($s === 'granted') {
            $granted += $c;
        }
    }

    return [
        'total' => $total,
        'pending' => $pending,
        'granted' => $granted,
        'by_status' => $byStatus
    ];
}
