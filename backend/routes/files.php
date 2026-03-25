<?php
// ============================================================================
// Files Route — จัดการไฟล์เอกสาร (ประกาศ/คำสั่ง/แบบฟอร์ม)
// ============================================================================

function handleFiles(PDO $pdo, string $method, array $path): void {
    $id = $path[1] ?? null;
    $action = $path[2] ?? null;

    if ($method === 'GET') {
        if ($id && $action === 'download') {
            // ดาวน์โหลดไฟล์
            $stmt = $pdo->prepare("SELECT * FROM file_attachments WHERE file_id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetch();
            if (!$file || !file_exists($file['file_path'])) jsonResponse(['error' => 'ไม่พบไฟล์'], 404);

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
            readfile($file['file_path']);
            exit;
        }

        // รายการไฟล์
        $category = $_GET['category'] ?? '';
        $page = intval($_GET['page'] ?? 1);

        $where = "WHERE 1=1";
        $params = [];
        if ($category) { $where .= " AND category = ?"; $params[] = $category; }

        $countSql = "SELECT COUNT(*) FROM file_attachments $where";
        $dataSql = "SELECT * FROM file_attachments $where ORDER BY created_at DESC";
        $result = paginate($pdo, $countSql, $dataSql, $params, $page);
        foreach ($result['data'] as &$row) {
            appendThaiDates($row, ['created_at']);
        }
        unset($row);
        jsonResponse(['success' => true] + $result);

    } elseif ($method === 'POST') {
        $file = $_FILES['file'] ?? null;
        $category = $_POST['category'] ?? 'อื่นๆ';
        $description = $_POST['description'] ?? '';

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            jsonResponse(['error' => 'อัปโหลดล้มเหลว'], 400);
        }

        $dir = UPLOAD_DIR . "files/";
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $fileName = basename($file['name']);
        $filePath = $dir . time() . '_' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            jsonResponse(['error' => 'บันทึกไฟล์ล้มเหลว'], 500);
        }

        $stmt = $pdo->prepare("
            INSERT INTO file_attachments (file_name, file_path, file_size, file_type, category, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$fileName, $filePath, $file['size'], $file['type'], $category, $description]);
        jsonResponse(['success' => true, 'file_id' => $pdo->lastInsertId()], 201);

    } elseif ($method === 'DELETE' && $id) {
        $stmt = $pdo->prepare("SELECT file_path FROM file_attachments WHERE file_id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch();

        if ($file && file_exists($file['file_path'])) {
            @unlink($file['file_path']);
        }

        $stmt = $pdo->prepare("DELETE FROM file_attachments WHERE file_id = ?");
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'deleted' => $stmt->rowCount()]);
    }
}
