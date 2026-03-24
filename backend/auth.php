<?php
// ============================================================================
// JWT Authentication — Decoration Core
// HMAC-SHA256 ไม่ใช้ library ภายนอก (เหมือน smart-port)
// ============================================================================

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function generateJWT($userId, $role = 'admin') {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'iat' => time(),
        'exp' => time() + 28800, // หมดอายุ 8 ชม. (เพิ่มจาก 1 ชม.)
        'data' => [
            'user_id' => $userId,
            'role' => $role
        ]
    ]);

    $headerEncoded = base64url_encode($header);
    $payloadEncoded = base64url_encode($payload);

    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
    $signatureEncoded = base64url_encode($signature);

    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

function validateJWT($token) {
    if (!$token) return false;

    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

    // ตรวจสอบ signature
    $signature = base64url_decode($signatureEncoded);
    $expected = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);

    if (!hash_equals($signature, $expected)) return false;

    // ตรวจสอบ payload
    $payload = json_decode(base64url_decode($payloadEncoded), true);
    if ($payload['exp'] < time()) return false;

    return $payload['data'];
}

function getAuthHeader() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return str_replace('Bearer ', '', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            return str_replace('Bearer ', '', $headers['Authorization']);
        }
    }
    return null;
}
