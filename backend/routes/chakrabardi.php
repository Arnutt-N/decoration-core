<?php
// ============================================================================
// Chakrabardi Route — เหรียญจักรพรรดิมาลา (≥25 ปีรับราชการ)
// ============================================================================

function handleChakrabardi(PDO $pdo, string $method, array $path): void {
    $action = $path[1] ?? '';
    $id = $path[2] ?? null;

    switch ($action) {

        // GET /chakrabardi/personnel — รายชื่อทั้งหมดพร้อม tab_status
        case 'personnel':
            if ($id && is_numeric($id)) {
                // GET /chakrabardi/personnel/:personnelId — รายละเอียดบุคคลเดี่ยว
                $stmt = $pdo->prepare("
                    SELECT p.*,
                           pos.position_name AS current_position,
                           COALESCE(p.org_name, o.org_name) AS department
                    FROM personnel p
                    LEFT JOIN `position` pos ON p.current_position_id = pos.position_id
                    LEFT JOIN organization o ON p.current_org_id = o.org_id
                    WHERE p.personnel_id = ?
                ");
                $stmt->execute([$id]);
                $person = $stmt->fetch();
                if (!$person) jsonResponse(['error' => 'ไม่พบบุคลากร'], 404);

                // คำนวณอายุราชการ
                $service = $person['hire_date'] ? calculateServiceYears($person['hire_date']) : null;
                $completion25y = $person['hire_date'] ? calculateCompletion25yDate($person['hire_date']) : null;
                $retirement = $person['birth_date'] ? calculateRetirementDate($person['birth_date']) : null;

                // คำขอทั้งหมด
                $stmt2 = $pdo->prepare("
                    SELECT * FROM chakrabardi_requests
                    WHERE personnel_id = ? ORDER BY created_at DESC
                ");
                $stmt2->execute([$id]);
                $requests = $stmt2->fetchAll();

                // ประวัติได้รับ
                $stmt3 = $pdo->prepare("
                    SELECT * FROM chakrabardi_history
                    WHERE personnel_id = ? ORDER BY award_year DESC
                ");
                $stmt3->execute([$id]);
                $history = $stmt3->fetchAll();

                appendThaiDates($person, ['birth_date', 'hire_date', 'retirement_date', 'position_level_start_date']);
                foreach ($requests as &$req) {
                    appendThaiDates($req, ['service_start_date', 'submit_date', 'completion_25y_date', 'retirement_date', 'created_at']);
                }
                unset($req);
                foreach ($history as &$h) {
                    appendThaiDates($h, ['award_date', 'submit_date']);
                }
                unset($h);

                jsonResponse([
                    'success' => true,
                    'data' => [
                        'personnel' => $person,
                        'service' => $service,
                        'completion_25y_date' => $completion25y,
                        'completion_25y_date_thai' => formatThaiDate($completion25y),
                        'retirement_date' => $retirement ?? $person['retirement_date'],
                        'retirement_date_thai' => formatThaiDate($retirement ?? $person['retirement_date']),
                        'requests' => $requests,
                        'history' => $history,
                        'has_medal' => count($history) > 0,
                    ]
                ]);
                return;
            }

            // GET /chakrabardi/personnel?tab=&search=&page=
            $tab = $_GET['tab'] ?? 'all';
            $search = $_GET['search'] ?? '';
            $org = $_GET['org'] ?? '';
            $page = intval($_GET['page'] ?? 1);

            $baseSelect = "
                SELECT p.personnel_id, p.citizen_id, p.prefix, p.first_name, p.last_name,
                       p.rank_name, p.hire_date, p.birth_date, p.current_level_code,
                       p.position_type, p.position_level_name,
                       p.discipline_status, p.discipline_detail, p.retirement_date,
                       COALESCE(p.org_name, o.org_name) AS department,
                       MAX(pos.position_name) AS current_position,
                       TIMESTAMPDIFF(YEAR, p.hire_date, CURDATE()) AS service_years_approx,
                       DATE_ADD(p.hire_date, INTERVAL 25 YEAR) AS completion_25y_date,
                       MAX(h.award_id) AS has_medal,
                       MAX(h.award_year) AS medal_year,
                       MAX(h.award_date) AS medal_date,
                       MAX(h.remarks) AS medal_remarks,
                       MAX(dr.request_id) AS active_request_id,
                       MAX(dr.deferral_reason) AS deferral_reason,
                       MAX(dr.deferral_year) AS deferral_year,
                       MAX(dr.status) AS request_status
            ";

            $baseFrom = "
                FROM personnel p
                LEFT JOIN `position` pos ON p.current_position_id = pos.position_id
                LEFT JOIN organization o ON p.current_org_id = o.org_id
                LEFT JOIN chakrabardi_history h ON p.personnel_id = h.personnel_id
                LEFT JOIN chakrabardi_requests dr ON p.personnel_id = dr.personnel_id
                    AND dr.request_id = (
                        SELECT MAX(r2.request_id) FROM chakrabardi_requests r2
                        WHERE r2.personnel_id = p.personnel_id
                    )
            ";

            $where = "WHERE p.is_active = 1 AND p.hire_date IS NOT NULL";
            $params = [];

            // Tab filter
            switch ($tab) {
                case 'eligible':
                    $where .= " AND DATE_ADD(p.hire_date, INTERVAL 25 YEAR) <= CURDATE()
                                AND h.award_id IS NULL
                                AND (dr.deferral_reason IS NULL OR dr.deferral_reason = '')";
                    break;
                case 'pending':
                    $where .= " AND DATE_ADD(p.hire_date, INTERVAL 25 YEAR) > CURDATE()";
                    break;
                case 'awarded':
                    $where .= " AND h.award_id IS NOT NULL";
                    break;
                case 'deferred':
                    $where .= " AND h.award_id IS NULL
                                AND dr.deferral_reason IS NOT NULL AND dr.deferral_reason != ''";
                    break;
            }

            if ($search) {
                $where .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.citizen_id LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if ($org) {
                $where .= " AND (p.org_name LIKE ? OR o.org_name LIKE ?)";
                $orgTerm = "%{$org}%";
                $params[] = $orgTerm;
                $params[] = $orgTerm;
            }

            $countSql = "SELECT COUNT(DISTINCT p.personnel_id) $baseFrom $where";
            $dataSql = "$baseSelect $baseFrom $where GROUP BY p.personnel_id ORDER BY p.hire_date ASC";

            $result = paginate($pdo, $countSql, $dataSql, $params, $page);
            $dateFields = ['hire_date', 'completion_25y_date', 'retirement_date', 'medal_date'];
            foreach ($result['data'] as &$row) {
                appendThaiDates($row, $dateFields);
            }
            unset($row);

            // Tab counts (แยก query เพื่อแสดง badge)
            $countBase = "FROM personnel p
                LEFT JOIN chakrabardi_history h ON p.personnel_id = h.personnel_id
                LEFT JOIN chakrabardi_requests dr ON p.personnel_id = dr.personnel_id
                    AND dr.request_id = (SELECT MAX(r2.request_id) FROM chakrabardi_requests r2 WHERE r2.personnel_id = p.personnel_id)
                WHERE p.is_active = 1 AND p.hire_date IS NOT NULL";

            $tabCounts = [];
            $countQueries = [
                'all' => "SELECT COUNT(DISTINCT p.personnel_id) $countBase",
                'eligible' => "SELECT COUNT(DISTINCT p.personnel_id) $countBase
                    AND DATE_ADD(p.hire_date, INTERVAL 25 YEAR) <= CURDATE()
                    AND h.award_id IS NULL
                    AND (dr.deferral_reason IS NULL OR dr.deferral_reason = '')",
                'pending' => "SELECT COUNT(DISTINCT p.personnel_id) $countBase
                    AND DATE_ADD(p.hire_date, INTERVAL 25 YEAR) > CURDATE()",
                'awarded' => "SELECT COUNT(DISTINCT p.personnel_id) $countBase
                    AND h.award_id IS NOT NULL",
                'deferred' => "SELECT COUNT(DISTINCT p.personnel_id) $countBase
                    AND h.award_id IS NULL
                    AND dr.deferral_reason IS NOT NULL AND dr.deferral_reason != ''",
            ];
            foreach ($countQueries as $key => $sql) {
                $tabCounts[$key] = (int) $pdo->query($sql)->fetchColumn();
            }

            jsonResponse(['success' => true, 'tab_counts' => $tabCounts] + $result);
            break;

        // GET /chakrabardi/eligible — backwards compatible
        case 'eligible':
            $minYears = 25;
            $stmt = $pdo->prepare("
                SELECT p.personnel_id, p.citizen_id, p.prefix, p.first_name, p.last_name,
                       p.hire_date, p.current_level_code, p.position_type, p.position_level_name,
                       p.org_name, p.retirement_date,
                       pos.position_name, o.org_name AS org_department,
                       TIMESTAMPDIFF(YEAR, p.hire_date, CURDATE()) as service_years,
                       DATE_ADD(p.hire_date, INTERVAL 25 YEAR) AS completion_25y_date
                FROM personnel p
                LEFT JOIN `position` pos ON p.current_position_id = pos.position_id
                LEFT JOIN organization o ON p.current_org_id = o.org_id
                WHERE p.is_active = 1
                  AND p.hire_date IS NOT NULL
                  AND TIMESTAMPDIFF(YEAR, p.hire_date, CURDATE()) >= ?
                  AND p.personnel_id NOT IN (SELECT personnel_id FROM chakrabardi_history)
                ORDER BY p.hire_date
            ");
            $stmt->execute([$minYears]);
            jsonResponse(['success' => true, 'min_years' => $minYears, 'data' => $stmt->fetchAll()]);
            break;

        // GET /chakrabardi/history/:personnel_id
        case 'history':
            if (!$id) jsonResponse(['error' => 'personnel_id required'], 400);
            $stmt = $pdo->prepare("SELECT * FROM chakrabardi_history WHERE personnel_id = ? ORDER BY award_year DESC");
            $stmt->execute([$id]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                appendThaiDates($row, ['award_date', 'submit_date']);
            }
            unset($row);
            jsonResponse(['success' => true, 'data' => $rows]);
            break;

        // GET/POST/PUT /chakrabardi — คำขอ
        default:
            if ($method === 'GET') {
                if ($action && is_numeric($action)) {
                    $stmt = $pdo->prepare("
                        SELECT r.*, p.prefix, p.first_name, p.last_name, p.hire_date,
                               p.current_level_code, p.position_type, p.position_level_name,
                               p.org_name, p.retirement_date, p.discipline_status,
                               COALESCE(p.org_name, o.org_name) AS department
                        FROM chakrabardi_requests r
                        LEFT JOIN personnel p ON r.personnel_id = p.personnel_id
                        LEFT JOIN organization o ON p.current_org_id = o.org_id
                        WHERE r.request_id = ?
                    ");
                    $stmt->execute([$action]);
                    $req = $stmt->fetch();
                    jsonResponse($req ? ['success' => true, 'data' => $req] : ['error' => 'ไม่พบคำขอ'], $req ? 200 : 404);
                }

                $year = $_GET['year'] ?? '';
                $status = $_GET['status'] ?? '';
                $page = intval($_GET['page'] ?? 1);

                $where = "WHERE 1=1";
                $params = [];
                if ($year) { $where .= " AND r.request_year = ?"; $params[] = $year; }
                if ($status) { $where .= " AND r.status = ?"; $params[] = $status; }

                $countSql = "SELECT COUNT(*) FROM chakrabardi_requests r $where";
                $dataSql = "
                    SELECT r.*, p.prefix, p.first_name, p.last_name, p.hire_date,
                           p.org_name, p.position_type, p.position_level_name
                    FROM chakrabardi_requests r
                    LEFT JOIN personnel p ON r.personnel_id = p.personnel_id
                    $where
                    ORDER BY r.created_at DESC
                ";
                $result = paginate($pdo, $countSql, $dataSql, $params, $page);
                jsonResponse(['success' => true] + $result);

            } elseif ($method === 'POST') {
                $data = getJsonInput();

                if (empty($data['personnel_id']) || !is_numeric($data['personnel_id'])) {
                    jsonResponse(['error' => 'personnel_id ต้องเป็นตัวเลข'], 400);
                }
                if (empty($data['service_start_date'])) {
                    jsonResponse(['error' => 'service_start_date จำเป็น'], 400);
                }

                $serviceYears = null;
                $serviceMonths = null;
                $serviceDays = null;
                $completion25y = null;
                $retirementDate = null;

                if (!empty($data['service_start_date'])) {
                    $svc = calculateServiceYears($data['service_start_date']);
                    $serviceYears = $svc['total_years'];
                    $serviceMonths = $svc['months'];
                    $serviceDays = $svc['days'];
                    $completion25y = calculateCompletion25yDate($data['service_start_date']);
                }

                // ดึง birth_date จาก personnel เพื่อคำนวณเกษียณ
                if (!empty($data['personnel_id'])) {
                    $stmt = $pdo->prepare("SELECT birth_date, retirement_date FROM personnel WHERE personnel_id = ?");
                    $stmt->execute([$data['personnel_id']]);
                    $person = $stmt->fetch();
                    if ($person) {
                        $retirementDate = $person['retirement_date']
                            ?? ($person['birth_date'] ? calculateRetirementDate($person['birth_date']) : null);
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO chakrabardi_requests
                    (personnel_id, request_year, service_start_date, service_years, is_eligible,
                     service_months, service_days, completion_25y_date, retirement_date,
                     submit_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
                ");
                $stmt->execute([
                    $data['personnel_id'],
                    $data['request_year'] ?? ((int)date('Y') + 543),
                    $data['service_start_date'],
                    $serviceYears,
                    $serviceYears >= 25 ? 1 : 0,
                    $serviceMonths,
                    $serviceDays,
                    $completion25y,
                    $retirementDate,
                    $data['submit_date'] ?? date('Y-m-d'),
                ]);
                jsonResponse(['success' => true, 'request_id' => $pdo->lastInsertId()], 201);

            } elseif ($method === 'PUT' && $action) {
                $data = getJsonInput();
                $sets = [];
                $params = [];

                $updatable = [
                    'status', 'discipline_status', 'notes', 'is_eligible',
                    'deferral_reason', 'deferral_year', 'has_discipline_detail',
                    'remarks', 'submit_date',
                    'service_months', 'service_days', 'completion_25y_date', 'retirement_date',
                ];

                foreach ($updatable as $field) {
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
