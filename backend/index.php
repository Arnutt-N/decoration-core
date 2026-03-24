<?php
// Health check endpoint
header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'status' => 'ok',
    'service' => 'Decoration Core API',
    'version' => '1.0.0',
    'timestamp' => date('c')
]);
