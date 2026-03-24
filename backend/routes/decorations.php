<?php
// ============================================================================
// Decorations Route — ช้างเผือก-มงกุฎไทย
// CRUD คำขอ + ตรวจสิทธิ์ + ประวัติ + ชั้นตรา + เกณฑ์
// ============================================================================

function handleDecorations(PDO $pdo, string $method, array $path): void {
    $action = $path[1] ?? '';
    $id = $path[2] ?? null;

    switch ($action) {

        // GET /decorations/levels — ชั้นตรา 12 ชั้น
        case 'levels':
            $stmt = $pdo->query("SELECT * FROM decoration_changpuak_levels ORDER BY sort_order");
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // GET /decorations/criteria?pos_level=K2 — เกณฑ์ตามระดับตำแหน่ง
        case 'criteria':
            $posLevel = $_GET['pos_level'] ?? '';
            if ($posLevel) {
                $stmt = $pdo->prepare("SELECT * FROM decoration_changpuak_criteria WHERE pos_level = ? AND is_active = 1");
                $stmt->execute([$posLevel]);
            } else {
                $stmt = $pdo->query("SELECT * FROM decoration_changpuak_criteria WHERE is_active = 1 ORDER BY pos_level, target_level_abbr");
            }
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // GET /decorations/check/:personnel_id — ตรวจสิทธิ์
        case 'check':
            if (!$id) jsonResponse(['error' => 'personnel_id required'], 400);

            // ดึงข้อมูลบุคลากร
            $stmt = $pdo->prepare("SELECT * FROM personnel WHERE personnel_id = ?");
            $stmt->execute([$id]);
            $person = $stmt->fetch();
            if (!$person) jsonResponse(['error' => 'ไม่พบข้อมูลบุคลากร'], 404);

            // ดึงชั้นปัจจุบัน
            $stmt = $pdo->prepare("
                SELECT level_abbr, decoration_type, award_year
                FROM decoration_changpuak_history
                WHERE personnel_id = ?
                ORDER BY award_year DESC LIMIT 1
            ");
            $stmt->execute([$id]);
            $currentAward = $stmt->fetch();

            // ดึงเกณฑ์ตามระดับตำแหน่ง
            $stmt = $pdo->prepare("
                SELECT * FROM decoration_changpuak_criteria
                WHERE pos_level = ? AND is_active = 1
                ORDER BY target_level_abbr
            ");
            $stmt->execute([$person['current_level_code'] ?? '']);
            $criteria = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'personnel' => $person,
                'current_decoration' => $currentAward ?: null,
                'eligible_levels' => $criteria
            ]);
            break;

        // GET /decorations/history/:personnel_id — ประวัติได้รับ
        case 'history':
            if (!$id) jsonResponse(['error' => 'personnel_id required'], 400);

            $stmt = $pdo->prepare("
                SELECT h.*, l.level_name
                FROM decoration_changpuak_history h
                LEFT JOIN decoration_changpuak_levels l ON h.level_abbr = l.abbreviation
                WHERE h.personnel_id = ?
                ORDER BY h.award_year DESC
            ");
            $stmt->execute([$id]);
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // GET/POST/PUT /decorations — คำขอ (default)
        default:
            if ($method === 'GET') {
                // รายการคำขอ
                $year = $_GET['year'] ?? '';
                $status = $_GET['status'] ?? '';
                $type = $_GET['type'] ?? '';
                $page = intval($_GET['page'] ?? 1);

                $where = "WHERE 1=1";
                $params = [];

                if ($year) { $where .= " AND r.request_year = ?"; $params[] = $year; }
                if ($status) { $where .= " AND r.status = ?"; $params[] = $status; }
                if ($type) { $where .= " AND r.decoration_type = ?"; $params[] = $type; }

                if ($action && is_numeric($action)) {
                    // GET /decorations/:id — รายละเอียดคำขอ
                    $stmt = $pdo->prepare("
                        SELECT r.*, p.first_name, p.last_name, p.current_level_code
                        FROM decoration_changpuak_requests r
                        LEFT JOIN personnel p ON r.personnel_id = p.personnel_id
                        WHERE r.request_id = ?
                    ");
                    $stmt->execute([$action]);
                    $req = $stmt->fetch();
                    jsonResponse($req ? ['success' => true, 'data' => $req] : ['error' => 'ไม่พบคำขอ'], $req ? 200 : 404);
                }

                $countSql = "SELECT COUNT(*) FROM decoration_changpuak_requests r $where";
                $dataSql = "
                    SELECT r.*, p.first_name, p.last_name, p.current_level_code
                    FROM decoration_changpuak_requests r
                    LEFT JOIN personnel p ON r.personnel_id = p.personnel_id
                    $where
                    ORDER BY r.created_at DESC
                ";
                $result = paginate($pdo, $countSql, $dataSql, $params, $page);
                jsonResponse(['success' => true] + $result);

            } elseif ($method === 'POST') {
                // สร้างคำขอ
                $data = getJsonInput();
                $stmt = $pdo->prepare("
                    INSERT INTO decoration_changpuak_requests
                    (personnel_id, request_year, current_level_abbr, requested_level_abbr,
                     decoration_type, pos_level, salary, years_in_level, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')
                ");
                $stmt->execute([
                    $data['personnel_id'],
                    $data['request_year'] ?? ((int)date('Y') + 543),
                    $data['current_level_abbr'] ?? null,
                    $data['requested_level_abbr'],
                    $data['decoration_type'],
                    $data['pos_level'] ?? null,
                    $data['salary'] ?? null,
                    $data['years_in_level'] ?? null
                ]);
                jsonResponse(['success' => true, 'request_id' => $pdo->lastInsertId()], 201);

            } elseif ($method === 'PUT' && $action) {
                // แก้ไขคำขอ
                $data = getJsonInput();
                $sets = [];
                $params = [];

                foreach (['status', 'eligibility_passed', 'eligibility_notes', 'discipline_status'] as $field) {
                    if (isset($data[$field])) {
                        $sets[] = "$field = ?";
                        $params[] = $data[$field];
                    }
                }

                if (empty($sets)) jsonResponse(['error' => 'ไม่มีข้อมูลที่จะอัปเดต'], 400);

                $params[] = $action;
                $stmt = $pdo->prepare("UPDATE decoration_changpuak_requests SET " . implode(', ', $sets) . " WHERE request_id = ?");
                $stmt->execute($params);
                jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
            }
            break;
    }
}
