<?php
// ============================================================================
// Users Route — จัดการผู้ใช้ + บทบาท
// ============================================================================

function handleUsers(PDO $pdo, string $method, array $path): void {
    $id = $path[1] ?? null;

    if ($method === 'GET') {
        if ($id) {
            $stmt = $pdo->prepare("
                SELECT u.*, GROUP_CONCAT(r.role_name) as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.role_id
                WHERE u.user_id = ?
                GROUP BY u.user_id
            ");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            jsonResponse($user ? ['success' => true, 'data' => $user] : ['error' => 'ไม่พบผู้ใช้'], $user ? 200 : 404);
        }

        $stmt = $pdo->query("
            SELECT u.*, GROUP_CONCAT(r.role_name) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            GROUP BY u.user_id
            ORDER BY u.user_id
        ");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        $data = getJsonInput();
        $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (?)");
        $stmt->execute([$data['username']]);
        $userId = $pdo->lastInsertId();

        // กำหนด role (ถ้ามี)
        if (!empty($data['role_id'])) {
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$userId, $data['role_id']]);
        }

        jsonResponse(['success' => true, 'user_id' => $userId], 201);

    } elseif ($method === 'PUT' && $id) {
        $data = getJsonInput();
        if (isset($data['username'])) {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE user_id = ?");
            $stmt->execute([$data['username'], $id]);
        }
        if (isset($data['role_id'])) {
            $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$id]);
            $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$id, $data['role_id']]);
        }
        jsonResponse(['success' => true]);
    }
}
