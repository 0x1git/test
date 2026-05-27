<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';

// API token check
$expectedToken = getenv('GATEFLOW_API_TOKEN') ?: '';
$providedToken = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if ($expectedToken !== '' && (!is_string($providedToken) || $providedToken !== $expectedToken)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// Accept only POST JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['updates']) || !is_array($data['updates'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid payload']);
    exit;
}

$allowed = ['Not Checked In', 'Checked In', 'Boarded', 'No Show'];
$results = ['updated' => 0, 'errors' => []];

try {
    $db->beginTransaction();
    $stmt = $db->prepare('UPDATE passengers SET checkin_status = ? WHERE id = ?');
    foreach ($data['updates'] as $u) {
        $id = isset($u['id']) ? (int)$u['id'] : 0;
        $status = isset($u['status']) ? trim($u['status']) : '';
        if ($id <= 0 || !in_array($status, $allowed, true)) {
            $results['errors'][] = ['id' => $id, 'error' => 'invalid input'];
            continue;
        }
        if ($stmt->execute([$status, $id])) {
            $results['updated']++;
        } else {
            $results['errors'][] = ['id' => $id, 'error' => 'db error'];
        }
    }
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error', 'detail' => $e->getMessage()]);
    exit;
}

echo json_encode(['ok' => true, 'result' => $results], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
