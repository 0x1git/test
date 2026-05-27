<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';

// Optional API token: set GATEFLOW_API_TOKEN in the environment for API access
$expectedToken = getenv('GATEFLOW_API_TOKEN') ?: '';
$providedToken = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if ($expectedToken !== '' && (!is_string($providedToken) || $providedToken !== $expectedToken)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// Keep session guard for interactive use, but token can be used for programmatic access
if (!$expectedToken) {
    verifyStaffSession();
}

$terminal = $_GET['terminal'] ?? $_SESSION['assigned_terminal'] ?? '';
$searchTerm = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$allowedStatuses = ['Scheduled', 'Boarding', 'Delayed', 'Departed', 'Cancelled'];

// Pagination
$limit = max(1, min(200, intval($_GET['limit'] ?? 50)));
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Build where clause and params
$where = ' WHERE terminal = ? AND departure_time > NOW()';
$params = [$terminal];

if ($searchTerm !== '') {
    $where .= ' AND (flight_number LIKE ? OR destination LIKE ? OR gate LIKE ?)';
    $searchLike = '%' . $searchTerm . '%';
    array_push($params, $searchLike, $searchLike, $searchLike);
}

if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $where .= ' AND status = ?';
    $params[] = $statusFilter;
}

// Total count for paging
$countStmt = $db->prepare('SELECT COUNT(*) as c FROM flights' . $where);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = 'SELECT id, flight_number, destination, gate, status, departure_time FROM flights' . $where . ' ORDER BY departure_time ASC LIMIT ? OFFSET ?';

$execParams = array_merge($params, [$limit, $offset]);
$stmt = $db->prepare($sql);
$stmt->execute($execParams);
$flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['ok' => true, 'meta' => ['total' => $total, 'page' => $page, 'limit' => $limit], 'flights' => $flights], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
