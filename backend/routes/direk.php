<?php
// ============================================================================
// Direk Route — ดิเรกคุณาภรณ์
// จัดการข้อมูลอาสาสมัคร + คำขอ + ประวัติ + ตรวจสิทธิ์ + เอกสาร + รายงาน
// ============================================================================

function handleDirek(PDO $pdo, string $method, array $path): void {
    $resource = $path[1] ?? '';
    $id = $path[2] ?? null;

    switch ($resource) {

        // ==================== LEVELS ====================
        case 'levels':
            $stmt = $pdo->query("SELECT * FROM direk_levels ORDER BY sort_order");
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // ==================== REGULATIONS ====================
        case 'regulations':
            $stmt = $pdo->query("SELECT * FROM direk_regulations WHERE is_active = 1 ORDER BY effective_date DESC");
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // ==================== PERSONS (อาสาสมัคร) ====================
        case 'persons':
            if ($method === 'GET') {
                if ($id) {
                    // รายละเอียดบุคคล + ประวัติ
                    $stmt = $pdo->prepare("SELECT * FROM direk_persons WHERE person_id = ?");
                    $stmt->execute([$id]);
                    $person = $stmt->fetch();
                    if (!$person) jsonResponse(['error' => 'ไม่พบข้อมูล'], 404);

                    // ดึงประวัติรับพระราชทาน
                    $stmt = $pdo->prepare("
                        SELECT h.*, l.level_name, l.abbreviation
                        FROM direk_award_history h
                        LEFT JOIN direk_levels l ON h.level_id = l.level_id
                        WHERE h.person_id = ?
                        ORDER BY h.award_year DESC
                    ");
                    $stmt->execute([$id]);
                    $person['award_history'] = $stmt->fetchAll();

                    jsonResponse(['success' => true, 'data' => $person]);
                } else {
                    // ค้นหา
                    $search = $_GET['search'] ?? '';
                    $page = intval($_GET['page'] ?? 1);

                    $where = "WHERE is_active = 1";
                    $params = [];
                    if ($search) {
                        $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR citizen_id LIKE ?)";
                        $term = "%{$search}%";
                        $params = [$term, $term, $term];
                    }

                    $countSql = "SELECT COUNT(*) FROM direk_persons $where";
                    $dataSql = "SELECT * FROM direk_persons $where ORDER BY first_name, last_name";
                    $result = paginate($pdo, $countSql, $dataSql, $params, $page);
                    jsonResponse(['success' => true] + $result);
                }

            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $stmt = $pdo->prepare("
                    INSERT INTO direk_persons
                    (citizen_id, prefix, first_name, last_name, gender, birth_date, phone, address,
                     volunteer_type, volunteer_dept, volunteer_since, is_outstanding, outstanding_year)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['citizen_id'], $data['prefix'] ?? null,
                    $data['first_name'], $data['last_name'],
                    $data['gender'] ?? null, $data['birth_date'] ?? null,
                    $data['phone'] ?? null, $data['address'] ?? null,
                    $data['volunteer_type'] ?? null, $data['volunteer_dept'] ?? null,
                    $data['volunteer_since'] ?? null,
                    $data['is_outstanding'] ?? 0, $data['outstanding_year'] ?? null
                ]);
                jsonResponse(['success' => true, 'person_id' => $pdo->lastInsertId()], 201);

            } elseif ($method === 'PUT' && $id) {
                $data = getJsonInput();
                $allowed = ['prefix','first_name','last_name','gender','birth_date','phone','address',
                           'volunteer_type','volunteer_dept','volunteer_since','is_outstanding',
                           'outstanding_year','criminal_check_status','criminal_check_date',
                           'bankruptcy_check_status','bankruptcy_check_date'];
                $sets = [];
                $params = [];
                foreach ($allowed as $field) {
                    if (array_key_exists($field, $data)) {
                        $sets[] = "$field = ?";
                        $params[] = $data[$field];
                    }
                }
                if (empty($sets)) jsonResponse(['error' => 'ไม่มีข้อมูลที่จะอัปเดต'], 400);

                $params[] = $id;
                $stmt = $pdo->prepare("UPDATE direk_persons SET " . implode(', ', $sets) . " WHERE person_id = ?");
                $stmt->execute($params);
                jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
            }
            break;

        // ==================== AWARDS (ประวัติรับพระราชทาน) ====================
        case 'awards':
            if ($method === 'GET') {
                $personId = $_GET['person_id'] ?? $id;
                if (!$personId) jsonResponse(['error' => 'person_id required'], 400);

                $stmt = $pdo->prepare("
                    SELECT h.*, l.level_name, l.abbreviation
                    FROM direk_award_history h
                    LEFT JOIN direk_levels l ON h.level_id = l.level_id
                    WHERE h.person_id = ?
                    ORDER BY h.award_year DESC
                ");
                $stmt->execute([$personId]);
                jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);

            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $stmt = $pdo->prepare("
                    INSERT INTO direk_award_history (person_id, level_id, award_year, gazette_date, gazette_ref, request_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['person_id'], $data['level_id'], $data['award_year'],
                    $data['gazette_date'] ?? null, $data['gazette_ref'] ?? null,
                    $data['request_id'] ?? null
                ]);
                jsonResponse(['success' => true, 'award_id' => $pdo->lastInsertId()], 201);
            }
            break;

        // ==================== REQUESTS (คำขอ) ====================
        case 'requests':
            $subAction = $id;

            // GET /direk/requests/check/:person_id — ตรวจสิทธิ์อัตโนมัติ
            if ($subAction === 'check' && $method === 'GET') {
                $personId = $path[3] ?? null;
                if (!$personId) jsonResponse(['error' => 'person_id required'], 400);

                include_once __DIR__ . '/../engines/DirekEligibility.php';
                $engine = new DirekEligibility($pdo);
                jsonResponse($engine->check((int) $personId));
                break;
            }

            if ($method === 'GET') {
                if ($subAction && is_numeric($subAction)) {
                    // รายละเอียดคำขอ
                    $stmt = $pdo->prepare("
                        SELECT r.*, p.first_name, p.last_name, p.citizen_id, p.volunteer_type,
                               l.level_name, l.abbreviation
                        FROM direk_requests r
                        LEFT JOIN direk_persons p ON r.person_id = p.person_id
                        LEFT JOIN direk_levels l ON r.requested_level = l.level_id
                        WHERE r.request_id = ?
                    ");
                    $stmt->execute([$subAction]);
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

                $countSql = "SELECT COUNT(*) FROM direk_requests r $where";
                $dataSql = "
                    SELECT r.*, p.first_name, p.last_name, p.citizen_id,
                           l.level_name, l.abbreviation
                    FROM direk_requests r
                    LEFT JOIN direk_persons p ON r.person_id = p.person_id
                    LEFT JOIN direk_levels l ON r.requested_level = l.level_id
                    $where
                    ORDER BY r.created_at DESC
                ";
                $result = paginate($pdo, $countSql, $dataSql, $params, $page);
                jsonResponse(['success' => true] + $result);

            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $stmt = $pdo->prepare("
                    INSERT INTO direk_requests
                    (person_id, request_year, requested_level, current_level, request_type,
                     work_title, work_detail, is_group_work,
                     donation_amount, donation_purpose, submitted_dept, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
                ");
                $stmt->execute([
                    $data['person_id'],
                    $data['request_year'] ?? ((int)date('Y') + 543),
                    $data['requested_level'],
                    $data['current_level'] ?? null,
                    $data['request_type'],
                    $data['work_title'] ?? null,
                    $data['work_detail'] ?? null,
                    $data['is_group_work'] ?? 0,
                    $data['donation_amount'] ?? null,
                    $data['donation_purpose'] ?? null,
                    $data['submitted_dept'] ?? null
                ]);
                jsonResponse(['success' => true, 'request_id' => $pdo->lastInsertId()], 201);

            } elseif ($method === 'PUT' && $subAction) {
                $data = getJsonInput();
                $allowed = ['status','eligibility_passed','eligibility_notes',
                           'committee_decision','committee_date'];
                $sets = [];
                $params = [];
                foreach ($allowed as $field) {
                    if (isset($data[$field])) {
                        $sets[] = "$field = ?";
                        $params[] = $data[$field];
                    }
                }
                if (empty($sets)) jsonResponse(['error' => 'ไม่มีข้อมูลที่จะอัปเดต'], 400);

                $params[] = $subAction;
                $stmt = $pdo->prepare("UPDATE direk_requests SET " . implode(', ', $sets) . " WHERE request_id = ?");
                $stmt->execute($params);
                jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
            }
            break;

        // ==================== DOCUMENTS ====================
        case 'documents':
            if ($method === 'POST') {
                // อัปโหลดเอกสาร
                $requestId = intval($_POST['request_id'] ?? 0);
                $docType = $_POST['doc_type'] ?? 'อื่นๆ';
                $file = $_FILES['file'] ?? null;

                if ($requestId <= 0 || !$file) jsonResponse(['error' => 'ข้อมูลไม่ครบ'], 400);
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) jsonResponse(['error' => 'อัปโหลดล้มเหลว'], 400);

                $dir = UPLOAD_DIR . "direk/$requestId/";
                if (!is_dir($dir)) mkdir($dir, 0775, true);

                $fileName = basename($file['name']);
                $filePath = $dir . $fileName;
                if (!move_uploaded_file($file['tmp_name'], $filePath)) jsonResponse(['error' => 'บันทึกไฟล์ล้มเหลว'], 500);

                $stmt = $pdo->prepare("
                    INSERT INTO direk_documents (request_id, doc_type, file_name, file_path, file_size)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$requestId, $docType, $fileName, $filePath, $file['size']]);
                jsonResponse(['success' => true, 'doc_id' => $pdo->lastInsertId()], 201);

            } elseif ($method === 'GET' && $id) {
                $stmt = $pdo->prepare("SELECT * FROM direk_documents WHERE doc_id = ?");
                $stmt->execute([$id]);
                $doc = $stmt->fetch();
                if (!$doc || !file_exists($doc['file_path'])) jsonResponse(['error' => 'ไม่พบไฟล์'], 404);

                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
                readfile($doc['file_path']);
                exit;
            }
            break;

        // ==================== REPORTS ====================
        case 'reports':
            $reportType = $id;
            $year = $_GET['year'] ?? ((int)date('Y') + 543);

            if ($reportType === 'annual') {
                $stmt = $pdo->prepare("
                    SELECT r.*, p.first_name, p.last_name, p.citizen_id, p.volunteer_type,
                           l.level_name, l.abbreviation
                    FROM direk_requests r
                    LEFT JOIN direk_persons p ON r.person_id = p.person_id
                    LEFT JOIN direk_levels l ON r.requested_level = l.level_id
                    WHERE r.request_year = ?
                    ORDER BY l.sort_order, p.first_name
                ");
                $stmt->execute([$year]);
                jsonResponse(['success' => true, 'year' => $year, 'data' => $stmt->fetchAll()]);

            } elseif ($reportType === 'person' && isset($path[3])) {
                $personId = $path[3];
                $stmt = $pdo->prepare("SELECT * FROM direk_persons WHERE person_id = ?");
                $stmt->execute([$personId]);
                $person = $stmt->fetch();

                $stmt = $pdo->prepare("
                    SELECT h.*, l.level_name, l.abbreviation
                    FROM direk_award_history h
                    LEFT JOIN direk_levels l ON h.level_id = l.level_id
                    WHERE h.person_id = ?
                    ORDER BY h.award_year
                ");
                $stmt->execute([$personId]);
                $person['awards'] = $stmt->fetchAll();

                jsonResponse(['success' => true, 'data' => $person]);
            } else {
                jsonResponse(['error' => 'ระบุประเภทรายงาน: annual หรือ person/:id'], 400);
            }
            break;

        default:
            jsonResponse(['error' => 'Direk endpoint not found'], 404);
    }
}
