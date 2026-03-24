<?php
// ============================================================================
// Chakrabardi Route — เหรียญจักรพรรดิมาลา (≥25 ปีรับราชการ)
// ============================================================================

function handleChakrabardi(PDO $pdo, string $method, array $path): void {
    $action = $path[1] ?? '';
    $id = $path[2] ?? null;

    switch ($action) {

        // GET /chakrabardi/eligible?year= — รายชื่อผู้มีสิทธิ์ (อายุราชการ ≥25 ปี)
        case 'eligible':
            $year = $_GET['year'] ?? ((int)date('Y') + 543);
            $minYears = 25;

            $stmt = $pdo->prepare("
                SELECT p.personnel_id, p.citizen_id, p.first_name, p.last_name,
                       p.hire_date, p.current_level_code,
                       pos.position_name, o.org_name,
                       TIMESTAMPDIFF(YEAR, p.hire_date, CURDATE()) as service_years
                FROM personnel p
                LEFT JOIN `position` pos ON p.current_position_id = pos.position_id
                LEFT JOIN organization o ON p.current_org_id = o.org_id
                WHERE p.is_active = 1
                  AND p.hire_date IS NOT NULL
                  AND TIMESTAMPDIFF(YEAR, p.hire_date, CURDATE()) >= ?
                  AND p.personnel_id NOT IN (
                      SELECT personnel_id FROM chakrabardi_history
                  )
                ORDER BY p.hire_date
            ");
            $stmt->execute([$minYears]);
            jsonResponse(['success' => true, 'min_years' => $minYears, 'data' => $stmt->fetchAll()]);
            break;

        // GET /chakrabardi/history/:personnel_id — ประวัติได้รับ
        case 'history':
            if (!$id) jsonResponse(['error' => 'personnel_id required'], 400);
            $stmt = $pdo->prepare("SELECT * FROM chakrabardi_history WHERE personnel_id = ? ORDER BY award_year DESC");
            $stmt->execute([$id]);
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        default:
            if ($method === 'GET') {
                if ($action && is_numeric($action)) {
                    // รายละเอียดคำขอ
                    $stmt = $pdo->prepare("
                        SELECT r.*, p.first_name, p.last_name, p.hire_date, p.current_level_code
                        FROM chakrabardi_requests r
                        LEFT JOIN personnel p ON r.personnel_id = p.personnel_id
                        WHERE r.request_id = ?
                    ");
                    $stmt->execute([$action]);
                    $req = $stmt->fetch();
                    jsonResponse($req ? ['success' => true, 'data' => $req] : ['error' => 'ไม่พบคำขอ'], $req ? 200 : 404);
                }

                // รายการคำขอ
                $year = $_GET['year'] ?? '';
                $status = $_GET['status'] ?? '';
                $page = intval($_GET['page'] ?? 1);

                $where = "WHERE 1=1";
                $params = [];
                if ($year) { $where .= " AND r.request_year = ?"; $params[] = $year; }
                if ($status) { $where .= " AND r.status = ?"; $params[] = $status; }

                $countSql = "SELECT COUNT(*) FROM chakrabardi_requests r $where";
                $dataSql = "
                    SELECT r.*, p.first_name, p.last_name, p.hire_date
                    FROM chakrabardi_requests r
                    LEFT JOIN personnel p ON r.personnel_id = p.personnel_id
                    $where
                    ORDER BY r.created_at DESC
                ";
                $result = paginate($pdo, $countSql, $dataSql, $params, $page);
                jsonResponse(['success' => true] + $result);

            } elseif ($method === 'POST') {
                $data = getJsonInput();

                // คำนวณอายุราชการ
                $serviceYears = null;
                if (!empty($data['service_start_date'])) {
                    $svc = calculateServiceYears($data['service_start_date']);
                    $serviceYears = $svc['total_years'];
                }

                $stmt = $pdo->prepare("
                    INSERT INTO chakrabardi_requests
                    (personnel_id, request_year, service_start_date, service_years, is_eligible, status)
                    VALUES (?, ?, ?, ?, ?, 'draft')
                ");
                $stmt->execute([
                    $data['personnel_id'],
                    $data['request_year'] ?? ((int)date('Y') + 543),
                    $data['service_start_date'],
                    $serviceYears,
                    $serviceYears >= 25 ? 1 : 0
                ]);
                jsonResponse(['success' => true, 'request_id' => $pdo->lastInsertId()], 201);

            } elseif ($method === 'PUT' && $action) {
                $data = getJsonInput();
                $sets = [];
                $params = [];
                foreach (['status', 'discipline_status', 'notes'] as $field) {
                    if (isset($data[$field])) {
                        $sets[] = "$field = ?";
                        $params[] = $data[$field];
                    }
                }
                if (empty($sets)) jsonResponse(['error' => 'ไม่มีข้อมูลที่จะอัปเดต'], 400);

                $params[] = $action;
                $stmt = $pdo->prepare("UPDATE chakrabardi_requests SET " . implode(', ', $sets) . " WHERE request_id = ?");
                $stmt->execute($params);
                jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
            }
            break;
    }
}
