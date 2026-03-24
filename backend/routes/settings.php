<?php
// ============================================================================
// Settings Route — ตั้งค่าระบบ (key-value)
// ============================================================================

function handleSettings(PDO $pdo, string $method, array $path): void {
    $key = $path[1] ?? null;

    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key");
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);

    } elseif ($method === 'PUT' && $key) {
        $data = getJsonInput();
        $value = $data['value'] ?? '';

        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->execute([$key, $value, $value]);
        jsonResponse(['success' => true]);
    }
}
