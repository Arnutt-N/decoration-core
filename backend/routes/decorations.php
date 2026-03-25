<?php
// ============================================================================
// Decorations Route — ช้างเผือก-มงกุฎไทย
// CRUD คำขอ + ตรวจสิทธิ์ + ประวัติ + ชั้นตรา + เกณฑ์
// ============================================================================

function handleDecorations(PDO $pdo, string $method, array $path): void {
    $action = $path[1] ?? '';
    $id = $path[2] ?? null;

    switch ($action) {

        // GET /decorations/levels
        case 'levels':
            $stmt = $pdo->query("SELECT * FROM decoration_changpuak_levels ORDER BY sort_order");
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // GET /decorations/criteria?pos_level=K2
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

        // GET /decorations/check/:personnel_id
        case 'check':
            if (!$id) jsonResponse(['error' => 'personnel_id required'], 400);

            $stmt = $pdo->prepare("
                SELECT p.*, pos.position_name AS current_position, o.org_name AS department
                FROM personnel p
                LEFT JOIN `position` pos ON p.current_position_id = pos.position_id
                LEFT JOIN organization o ON p.current_org_id = o.org_id
                WHERE p.personnel_id = ?
            ");
            $stmt->execute([$id]);
            $person = $stmt->fetch();
            if (!$person) jsonResponse(['error' => 'ไม่พบข้อมูลบุคลากร'], 404);

            $stmt = $pdo->prepare("
                SELECT level_abbr, decoration_type, award_year, award_date
                FROM decoration_changpuak_history
                WHERE personnel_id = ?
                ORDER BY award_year DESC LIMIT 1
            ");
            $stmt->execute([$id]);
            $currentAward = $stmt->fetch();

            $stmt = $pdo->prepare("
                SELECT * FROM decoration_changpuak_criteria
                WHERE pos_level = ? AND is_active = 1
                ORDER BY target_level_abbr
            ");
            $stmt->execute([$person['current_level_code'] ?? '']);
            $criteria = $stmt->fetchAll();

            // ประวัติเครื่องราชฯ ทั้งหมด
            $stmt = $pdo->prepare("
                SELECT h.*, l.level_name
                FROM decoration_changpuak_history h
                LEFT JOIN decoration_changpuak_levels l ON h.level_abbr = l.abbreviation
                WHERE h.personnel_id = ?
                ORDER BY h.award_year ASC
            ");
            $stmt->execute([$id]);
            $history = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'personnel' => $person,
                'current_decoration' => $currentAward ?: null,
                'eligible_levels' => $criteria,
                'decoration_history' => $history
            ]);
            break;

        // GET /decorations/history/:personnel_id
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
                $year = $_GET['year'] ?? '';
                $status = $_GET['status'] ?? '';
                $type = $_GET['type'] ?? '';
                $search = $_GET['search'] ?? '';
                $page = intval($_GET['page'] ?? 1);

                // GET /decorations/:id — รายละเอียดคำขอเดี่ยว
                if ($action && is_numeric($action)) {
                    $stmt = $pdo->prepare("
                        SELECT r.*,
                               p.prefix, p.first_name, p.last_name, p.citizen_id,
                               p.rank_name, p.gender, p.birth_date, p.hire_date,
                               p.current_level_code, p.position_type, p.position_level_name,
                               p.salary, p.salary_5y, p.position_allowance,
                               p.position_level_start_date, p.position_start_date,
                               p.retirement_date, p.discipline_status, p.discipline_detail,
                               p.org_name, p.position_code,
                               COALESCE(p.org_name, o.org_name) AS department
                        FROM decoration_changpuak_requests r
                        LEFT JOIN personnel p ON r.personnel_id = p.personnel_id
                        LEFT JOIN organization o ON p.current_org_id = o.org_id
                        WHERE r.request_id = ?
                    ");
                    $stmt->execute([$action]);
                    $req = $stmt->fetch();
                    if (!$req) jsonResponse(['error' => 'ไม่พบคำขอ'], 404);

                    // ดึงประวัติเครื่องราชฯ
                    $stmt2 = $pdo->prepare("
                        SELECT h.level_abbr, h.decoration_type, h.award_year, h.award_date,
                               l.level_name
                        FROM decoration_changpuak_history h
                        LEFT JOIN decoration_changpuak_levels l ON h.level_abbr = l.abbreviation
                        WHERE h.personnel_id = ?
                        ORDER BY h.award_year ASC
                    ");
                    $stmt2->execute([$req['personnel_id']]);
                    $req['decoration_history'] = $stmt2->fetchAll();

                    appendThaiDates($req, ['birth_date', 'hire_date', 'position_level_start_date', 'position_start_date', 'retirement_date', 'created_at']);
                    foreach ($req['decoration_history'] as &$h) {
                        appendThaiDates($h, ['award_date']);
                    }
                    unset($h);

                    jsonResponse(['success' => true, 'data' => $req]);
                }

                // รายการคำขอ (list) — master view
                $where = "WHERE 1=1";
                $params = [];

                if ($year) { $where .= " AND r.request_year = ?"; $params[] = $year; }
                if ($status) { $where .= " AND r.status = ?"; $params[] = $status; }
                if ($type) { $where .= " AND r.decoration_type = ?"; $params[] = $type; }
                if ($search) {
                    $where .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.citizen_id LIKE ?)";
                    $searchTerm = "%{$search}%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }

                $countSql = "
                    SELECT COUNT(*)
                    FROM decoration_changpuak_requests r
                    LEFT JOIN personnel p ON r.personnel_id = p.personnel_id
                    $where
                ";
                $dataSql = "
                    SELECT r.request_id, r.personnel_id, r.request_year,
                           r.current_level_abbr, r.requested_level_abbr,
                           r.decoration_type, r.pos_level, r.salary, r.status,
                           r.eligibility_passed, r.eligibility_right,
                           r.thabanandorn_status,
                           r.created_at,
                           p.prefix, p.first_name, p.last_name,
                           p.rank_name, p.current_level_code, p.birth_date,
                           p.position_type, p.position_level_name,
                           p.salary_5y, p.position_allowance,
                           p.position_level_start_date, p.hire_date,
                           COALESCE(p.org_name, o.org_name) AS department,
                           cur_l.level_name AS current_level_name,
                           req_l.level_name AS requested_level_name
                    FROM decoration_changpuak_requests r
                    LEFT JOIN personnel p ON r.personnel_id = p.personnel_id
                    LEFT JOIN organization o ON p.current_org_id = o.org_id
                    LEFT JOIN decoration_changpuak_levels cur_l ON r.current_level_abbr = cur_l.abbreviation
                    LEFT JOIN decoration_changpuak_levels req_l ON r.requested_level_abbr = req_l.abbreviation
                    $where
                    ORDER BY r.created_at DESC
                ";
                $result = paginate($pdo, $countSql, $dataSql, $params, $page);
                $dateFields = ['birth_date', 'hire_date', 'position_level_start_date', 'created_at'];
                foreach ($result['data'] as &$row) {
                    appendThaiDates($row, $dateFields);
                }
                unset($row);
                jsonResponse(['success' => true] + $result);

            } elseif ($method === 'POST') {
                $data = getJsonInput();

                if (empty($data['personnel_id']) || !is_numeric($data['personnel_id'])) {
                    jsonResponse(['error' => 'personnel_id ต้องเป็นตัวเลข'], 400);
                }
                if (empty($data['requested_level_abbr'])) {
                    jsonResponse(['error' => 'requested_level_abbr จำเป็น'], 400);
                }
                if (empty($data['decoration_type']) || !in_array($data['decoration_type'], ['ช้างเผือก', 'มงกุฎไทย'])) {
                    jsonResponse(['error' => 'decoration_type ต้องเป็น ช้างเผือก หรือ มงกุฎไทย'], 400);
                }

                // Snapshot จาก personnel
                $snapshot = [];
                if (!empty($data['personnel_id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM personnel WHERE personnel_id = ?");
                    $stmt->execute([$data['personnel_id']]);
                    $person = $stmt->fetch();
                    if ($person) {
                        $snapshot = [
                            'salary_5y_at_request' => $person['salary_5y'],
                            'position_allowance_at_request' => $person['position_allowance'],
                            'position_type_at_request' => $person['position_type'],
                            'position_name_at_request' => $person['position_level_name'],
                        ];
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO decoration_changpuak_requests
                    (personnel_id, request_year, current_level_abbr, requested_level_abbr,
                     decoration_type, pos_level, salary, years_in_level, status,
                     salary_5y_at_request, position_allowance_at_request,
                     position_type_at_request, position_name_at_request)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['personnel_id'],
                    $data['request_year'] ?? ((int)date('Y') + 543),
                    $data['current_level_abbr'] ?? null,
                    $data['requested_level_abbr'],
                    $data['decoration_type'],
                    $data['pos_level'] ?? null,
                    $data['salary'] ?? null,
                    $data['years_in_level'] ?? null,
                    $snapshot['salary_5y_at_request'] ?? $data['salary_5y_at_request'] ?? null,
                    $snapshot['position_allowance_at_request'] ?? $data['position_allowance_at_request'] ?? null,
                    $snapshot['position_type_at_request'] ?? $data['position_type_at_request'] ?? null,
                    $snapshot['position_name_at_request'] ?? $data['position_name_at_request'] ?? null,
                ]);
                jsonResponse(['success' => true, 'request_id' => $pdo->lastInsertId()], 201);

            } elseif ($method === 'PUT' && $action) {
                $data = getJsonInput();
                $sets = [];
                $params = [];

                $updatable = [
                    'status', 'eligibility_passed', 'eligibility_notes',
                    'discipline_status', 'thabanandorn_status', 'thabanandorn_detail',
                    'eligibility_right', 'calculated_level_abbr'
                ];

                foreach ($updatable as $field) {
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
